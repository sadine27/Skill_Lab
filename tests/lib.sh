#!/usr/bin/env bash
# Shared bootstrap + assertion helpers for the test scripts.
# Sourced by smoke_test.sh and pentest.sh.
#
# Provides: boot_server, hit, ok, bad, assert_code, assert_body,
#           assert_not_body, assert_loc, finish.
# Requires: php (with pdo_sqlite) and curl.

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
HOST=127.0.0.1
DB="$(mktemp -t ws_test_XXXX.sqlite)"
JARDIR="$(mktemp -d)"
HDR="$JARDIR/hdr"; BODY="$JARDIR/body"
PASS=0; FAIL=0
SERVER_PID=""

cleanup() {
  [ -n "$SERVER_PID" ] && kill "$SERVER_PID" 2>/dev/null
  rm -f "$DB"; rm -rf "$JARDIR"
}
trap cleanup EXIT

# boot_server <port> — seed the DB and start the app pointed at it.
boot_server() {
  local port="$1"
  BASE="http://$HOST:$port"
  php "$ROOT/tests/seed_sqlite.php" "$DB" >/dev/null || { echo "seed failed"; exit 1; }
  WS_DB_DSN="sqlite:$DB" php -S "$HOST:$port" -t "$ROOT" >"$JARDIR/server.log" 2>&1 &
  SERVER_PID=$!
  local i
  for i in $(seq 1 40); do
    curl -s -o /dev/null "$BASE/auth/login.php" && return 0
    sleep 0.2
  done
  echo "server did not start; log:"; cat "$JARDIR/server.log"; exit 1
}

# hit ... — curl wrapper: body -> $BODY, headers -> $HDR, prints HTTP code.
hit() { curl -s -o "$BODY" -D "$HDR" -w '%{http_code}' "$@"; }

ok()  { PASS=$((PASS+1)); printf '  \033[32mPASS\033[0m %s\n' "$1"; }
bad() { FAIL=$((FAIL+1)); printf '  \033[31mFAIL\033[0m %s\n' "$1"; }

assert_code()     { [ "$2" = "$3" ] && ok "$1 (HTTP $3)" || bad "$1 (expected HTTP $2, got $3)"; }
assert_body()     { grep -q -- "$2" "$BODY" && ok "$1" || bad "$1 (missing: $2)"; }
assert_not_body() { grep -q -- "$2" "$BODY" && bad "$1 (unexpected: $2)" || ok "$1"; }
assert_loc()      { grep -i '^location:' "$HDR" | grep -q -- "$2" && ok "$1" || bad "$1 (Location lacks: $2)"; }

finish() {
  echo
  echo "-------------------------------------------"
  echo "$1: $PASS passed, $FAIL failed"
  echo "-------------------------------------------"
  [ "$FAIL" -eq 0 ]
}
