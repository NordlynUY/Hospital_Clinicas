#!/bin/bash
# Envía los respaldos al servicio backup-server del archivo docker-compose.yml.
set -e

BACKUP_ROOT="/home/hospitaldeclinicas/Backups/Nordlyn"
BACKUP_USER="backupuser"
BACKUP_HOST="127.0.0.1"
BACKUP_PORT="2222"

if [ ! -d "$BACKUP_ROOT" ]; then
    echo "No existe la carpeta de respaldos: $BACKUP_ROOT"
    exit 1
fi

if ! command -v rsync >/dev/null 2>&1; then
    echo "Instale rsync: sudo apt install rsync -y"
    exit 1
fi

echo "Se solicitará la contraseña ficticia del servidor Docker de respaldo."
rsync -avz -e "ssh -p $BACKUP_PORT -o StrictHostKeyChecking=accept-new" \
    "$BACKUP_ROOT/" \
    "${BACKUP_USER}@${BACKUP_HOST}:/srv/backups/nordlyn/"

echo "Copia enviada al contenedor backup-server."
