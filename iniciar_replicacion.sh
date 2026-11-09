#!/bin/bash
# Inicializa la replicación MariaDB entre db-primary y db-replica.
# Ejecutar desde la carpeta que contiene docker-compose.yml.

set -Eeuo pipefail

if [ ! -f .env ]; then
    echo "ERROR: No se encontró el archivo .env."
    echo "Ejecute este script desde la carpeta SIGSM_Nordlyn."
    exit 1
fi

set -a
. ./.env
set +a

for variable in DB_ROOT_PASSWORD DB_REPL_PASSWORD; do
    if [ -z "${!variable:-}" ]; then
        echo "ERROR: Falta definir $variable en .env."
        exit 1
    fi
done

TEMP_DUMP="$(mktemp)"
trap 'rm -f "$TEMP_DUMP"' EXIT

echo "Esperando a que MariaDB esté disponible..."
for servicio in db-primary db-replica; do
    disponible=0
    for intento in $(seq 1 60); do
        if docker compose exec -T "$servicio" \
            mariadb-admin -uroot -p"${DB_ROOT_PASSWORD}" ping >/dev/null 2>&1; then
            disponible=1
            break
        fi
        sleep 3
    done
    if [ "$disponible" -ne 1 ]; then
        echo "ERROR: $servicio no respondió después de 180 segundos."
        echo "Revise: docker compose ps"
        echo "Revise: docker compose logs --tail=80 $servicio"
        exit 1
    fi
done

echo "Generando una copia inicial de sigsm_nordlyn..."
if ! docker compose exec -T db-primary \
    mariadb-dump -uroot -p"${DB_ROOT_PASSWORD}" \
    --master-data=2 --single-transaction --databases sigsm_nordlyn > "$TEMP_DUMP"; then
    echo "ERROR: No se pudo generar el respaldo inicial."
    echo "Compruebe que exista la base sigsm_nordlyn en db-primary."
    exit 1
fi

# MariaDB puede escribir la sentencia CHANGE MASTER TO en una o varias
# líneas. Se extraen los dos valores de forma independiente del formato.
COORDENADAS="$(perl -0777 -ne '
    if (/CHANGE\s+MASTER\s+TO\s+(.*?);/is) {
        $s = $1;
        ($f) = $s =~ /MASTER_LOG_FILE\s*=\s*[\x27"]([^\x27"]+)[\x27"]/i;
        ($p) = $s =~ /MASTER_LOG_POS\s*=\s*(\d+)/i;
        if (defined $f && defined $p) {
            print "$f\n$p\n";
            exit 0;
        }
    }
    exit 1;
' "$TEMP_DUMP" 2>/dev/null || true)"

LOG_FILE="$(printf '%s\n' "$COORDENADAS" | sed -n '1p')"
LOG_POS="$(printf '%s\n' "$COORDENADAS" | sed -n '2p')"

if [ -z "$LOG_FILE" ] || [ -z "$LOG_POS" ]; then
    echo "ERROR: No se pudieron leer las coordenadas del servidor principal."
    echo "Verificando el estado de los registros binarios..."
    docker compose exec -T db-primary mariadb -uroot -p"${DB_ROOT_PASSWORD}" \
        -e "SHOW VARIABLES LIKE 'log_bin'; SHOW MASTER STATUS;" || true
    echo
    echo "Fragmento de la información de replicación generada por mariadb-dump:"
    grep -in -A3 -B2 'CHANGE MASTER' "$TEMP_DUMP" | head -20 || true
    echo
    echo "Compruebe que log_bin esté habilitado en docker/mariadb/primary.cnf"
    exit 1
fi

echo "Coordenadas obtenidas: archivo $LOG_FILE, posición $LOG_POS."
echo "Preparando la base de datos secundaria..."

docker compose exec -T db-replica mariadb -uroot -p"${DB_ROOT_PASSWORD}" \
    -e "STOP SLAVE; RESET SLAVE ALL;" >/dev/null 2>&1 || true

docker compose exec -T db-replica mariadb -uroot -p"${DB_ROOT_PASSWORD}" < "$TEMP_DUMP"

docker compose exec -T db-replica mariadb -uroot -p"${DB_ROOT_PASSWORD}" -e "
CHANGE MASTER TO
  MASTER_HOST='db-primary',
  MASTER_USER='replicador',
  MASTER_PASSWORD='${DB_REPL_PASSWORD}',
  MASTER_PORT=3306,
  MASTER_LOG_FILE='${LOG_FILE}',
  MASTER_LOG_POS=${LOG_POS};
START SLAVE;
"

echo
echo "Configuración inicial de replicación finalizada."
echo "db-primary funciona como maestro y db-replica como réplica."
echo "Para consultar el estado, ejecute:"
echo "docker compose exec db-replica mariadb -uroot -p'${DB_ROOT_PASSWORD}' -e 'SHOW SLAVE STATUS\\G'"
