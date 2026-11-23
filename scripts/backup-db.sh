#!/usr/bin/env sh
set -eu
: "${ZAZA_BACKUP_DIR:?Configura el directorio de respaldos}"
: "${PGHOST:?Configura PGHOST}"
: "${PGUSER:?Configura PGUSER}"
: "${PGDATABASE:?Configura PGDATABASE}"
umask 077
mkdir -p "$ZAZA_BACKUP_DIR"
timestamp=$(date -u +%Y%m%dT%H%M%SZ)
backup=$(mktemp "$ZAZA_BACKUP_DIR/zaza-$timestamp-XXXXXX")
trap 'rm -f "$backup"' EXIT
PGSSLMODE=require pg_dump --no-password --format=custom --schema=public --file="$backup"
mv "$backup" "$backup.dump"
