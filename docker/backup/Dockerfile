FROM debian:12-slim

RUN apt-get update && apt-get install -y --no-install-recommends openssh-server rsync \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /run/sshd /srv/backups

COPY start.sh /usr/local/bin/start-backup-server
RUN chmod 755 /usr/local/bin/start-backup-server

EXPOSE 22
CMD ["/usr/local/bin/start-backup-server"]
