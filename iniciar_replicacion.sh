#!/bin/bash
# Inicializa la replicación MariaDB entre db-primary y db-replica.
# Ejecutar desde la carpeta que contiene docker-compose.yml.

set -Eeuo pipefail

if [ ! -f .env ]; then
    echo "No se encontró .env."
    exit 1
fi

set -a
. ./.env
set +a

TEMP_DUMP="$(mktemp)"
trap 'rm -f "$TEMP_DUMP"' EXIT

echo "Esperando a que MariaDB esté disponible..."
until docker compose exec -T db-primary mariadb-admin -uroot -p"${DB_ROOT_PASSWORD}" ping >/dev/null 2>&1; do sleep 3; done
until docker compose exec -T db-replica mariadb-admin -uroot -p"${DB_ROOT_PASSWORD}" ping >/dev/null 2>&1; do sleep 3; done

echo "Generando una copia inicial de sigsm_nordlyn..."
docker compose exec -T db-primary mariadb-dump -uroot -p"${DB_ROOT_PASSWORD}" \
    --master-data=2 --single-transaction --databases sigsm_nordlyn > "$TEMP_DUMP"

CHANGE_LINE="$(grep -m 1 'CHANGE MASTER TO MASTER_LOG_FILE' "$TEMP_DUMP" | sed 's/^-- //')"
LOG_FILE="$(printf '%s' "$CHANGE_LINE" | sed -n "s/.*MASTER_LOG_FILE='\\([^']*\\)'.*/\\1/p")"
LOG_POS="$(printf '%s' "$CHANGE_LINE" | sed -n 's/.*MASTER_LOG_POS=\\([0-9]*\\).*/\\1/p')"

if [ -z "$LOG_FILE" ] || [ -z "$LOG_POS" ]; then
    echo "No se pudieron obtener las coordenadas del servidor principal."
    exit 1
fi

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

echo "Configuración inicial de replicación finalizada."
echo "db-primary funciona como maestro y db-replica como réplica."
