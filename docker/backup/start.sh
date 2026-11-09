#!/bin/bash
set -e

BACKUP_USER="${BACKUP_USER:-backupuser}"
BACKUP_PASSWORD="${BACKUP_PASSWORD:-Backup_Nordlyn_2026!}"

if ! id "$BACKUP_USER" >/dev/null 2>&1; then
    useradd -m -s /bin/bash "$BACKUP_USER"
fi

echo "$BACKUP_USER:$BACKUP_PASSWORD" | chpasswd
mkdir -p /srv/backups/nordlyn
chown -R "$BACKUP_USER:$BACKUP_USER" /srv/backups
chmod 750 /srv/backups /srv/backups/nordlyn

cat > /etc/ssh/sshd_config.d/backup.conf <<EOF
PermitRootLogin no
PasswordAuthentication yes
PubkeyAuthentication yes
AllowUsers $BACKUP_USER
EOF

ssh-keygen -A
exec /usr/sbin/sshd -D -e
