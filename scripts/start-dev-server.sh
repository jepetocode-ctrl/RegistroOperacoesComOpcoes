#!/usr/bin/env bash
set -euo pipefail

PORT="${PORT:-8000}"
MAX_PORT="${MAX_PORT:-8100}"

is_port_free() {
  python3 - "$1" <<'PY'
import socket
import sys

port = int(sys.argv[1])
with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    try:
        sock.bind(("127.0.0.1", port))
    except OSError:
        sys.exit(1)
    sys.exit(0)
PY
}

while (( PORT <= MAX_PORT )); do
  if is_port_free "$PORT"; then
    break
  fi
  PORT=$((PORT + 1))
done

if (( PORT > MAX_PORT )); then
  echo "Nenhuma porta livre encontrada entre 8000 e 8100." >&2
  exit 1
fi

echo "Iniciando servidor em http://127.0.0.1:${PORT}"
exec php -S "127.0.0.1:${PORT}" -t public
