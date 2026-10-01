#!/usr/bin/env bash
set -Eeuo pipefail

: "${SSH_PRIVATE_KEY:?SSH_PRIVATE_KEY is required}"
: "${SSH_HOST:?SSH_HOST is required}"
: "${SSH_USER:?SSH_USER is required}"
: "${APP_DIR:?APP_DIR is required}"

[[ "$SSH_HOST" =~ ^[a-zA-Z0-9][a-zA-Z0-9.-]*$ ]] || { echo "::error::SSH_HOST tidak valid"; exit 1; }
[[ "$SSH_USER" =~ ^[a-zA-Z_][a-zA-Z0-9_-]*$ ]] || { echo "::error::SSH_USER tidak valid"; exit 1; }
[[ "${SSH_PORT:-22}" =~ ^[0-9]+$ ]] || { echo "::error::SSH_PORT harus angka"; exit 1; }
[[ "$APP_DIR" =~ ^/[a-zA-Z0-9_./-]+$ && "$APP_DIR" != "/" ]] || { echo "::error::APP_DIR tidak valid"; exit 1; }

mkdir -p ~/.ssh
chmod 700 ~/.ssh
printf '%s\n' "$SSH_PRIVATE_KEY" > ~/.ssh/id_ed25519
chmod 600 ~/.ssh/id_ed25519

ssh-keyscan -T 10 -p "${SSH_PORT:-22}" "$SSH_HOST" > ~/.ssh/known_hosts || {
  echo "::error::Tidak dapat mengambil SSH host key."
  exit 1
}

test -s ~/.ssh/known_hosts || { echo "::error::known_hosts kosong"; exit 1; }
chmod 600 ~/.ssh/known_hosts
