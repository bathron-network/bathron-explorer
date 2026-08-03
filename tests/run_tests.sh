#!/bin/bash
# BATHRON explorer — configuration & anti-secret test suite.
# Requires: php-cli. Run from anywhere: tests/run_tests.sh
set -u
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
FAIL=0
ok()   { echo "  ok  - $1"; }
bad()  { echo "  FAIL- $1"; FAIL=1; }

command -v php >/dev/null || { echo "php-cli required"; exit 2; }

echo "== lint =="
for f in "$ROOT"/public/index.php "$ROOT"/src/*.php "$ROOT"/tracker/*.php; do
  php -l "$f" >/dev/null 2>&1 && ok "php -l $(basename "$f")" || bad "php -l $(basename "$f")"
done

echo "== fail-closed (no credentials) =="
OUT=$(BATHRON_RPC_CONFIG_FILE=/nonexistent-bathron-test php "$ROOT/public/index.php" 2>/dev/null)
echo "$OUT" | grep -q "temporarily unavailable" && ok "maintenance page served" || bad "maintenance page missing"
echo "$OUT" | grep -qiE "rpc_user|rpc_pass|password|27175|stack trace" && bad "leak in error output" || ok "no connection detail leaked"

echo "== config parsing (fixture) =="
FIX="$(mktemp)"; trap 'rm -f "$FIX"' EXIT
printf 'BATHRON_RPC_USER=u1\nBATHRON_RPC_PASS=legacy-alias-pw\nBATHRON_RPC_PORT=12345\n# comment\n' > "$FIX"
R=$(BATHRON_RPC_CONFIG_FILE="$FIX" php -r "require '$ROOT/src/config.php'; \$c=bathron_rpc_config(); echo \$c===false?'FALSE':\$c['user'].'|'.\$c['pass'].'|'.\$c['port'];")
[ "$R" = "u1|legacy-alias-pw|12345" ] && ok "env-file parse + PASS alias" || bad "config parse ($R)"
R2=$(BATHRON_RPC_CONFIG_FILE=/nonexistent php -r "require '$ROOT/src/config.php'; var_export(bathron_rpc_config());")
[ "$R2" = "false" ] && ok "missing config -> false (fail-closed)" || bad "missing config returned $R2"

echo "== anti-secret / anti-server-path guards =="
G() { # pattern, label
  if grep -rInE "$1" "$ROOT" --include='*.php' --include='*.md' --include='*.example' --include='*.service' \
        | grep -v "tests/run_tests.sh" | grep -vE "REPLACE_WITH|BATHRON_RPC_(USER|PASSWORD|PASS|HOST|PORT)=$|example" ; then
    bad "$2"
  else
    ok "$2"
  fi
}
G "rpcpassword=[A-Za-z0-9]" "no literal rpcpassword"
G "RPC_PASS(WORD)? *= *'[^']" "no literal RPC password constant"
G "/home/[a-z]" "no /home/... server paths"
G "testnet5" "no hardcoded testnet5 datadir"
G "57\.131\.33\.|162\.19\.251\.|51\.75\.31\.|37\.59\.114\." "no operator/fleet IPs"

echo "== network separation (one codebase, several chains) =="
# The Bitcoin source network must never be hardcoded: it comes from the node
# (getbtcsyncstatus.network) or from configuration. A literal 'signet' fallback
# is exactly the bug that let a disposable chain be shown as a monetary one.
if grep -rIn "signet" "$ROOT/public" "$ROOT/src" "$ROOT/tracker" 2>/dev/null | grep -v "tests/"; then
  bad "no hardcoded Bitcoin network in application code"
else
  ok "no hardcoded Bitcoin network in application code"
fi
# Caches must be namespaced per deployment, otherwise two chains share state.
if grep -rIn "sys_get_temp_dir() *\. *'/bathron" "$ROOT/public" "$ROOT/src" 2>/dev/null; then
  bad "cache paths namespaced per network"
else
  ok "cache paths namespaced per network"
fi
# Unknown network must yield NO external link rather than a guessed one.
R3=$(php -r "require '$ROOT/src/config.php'; var_export(bathron_btc_explorer_base('nope'));")
[ "$R3" = "''" ] && ok "unknown network -> no external link (fail closed)" || bad "unknown network guessed a link ($R3)"
R4=$(php -r "require '$ROOT/src/config.php'; echo bathron_btc_explorer_base('testnet4');")
[ "$R4" = "https://mempool.space/testnet4" ] && ok "testnet4 -> testnet4 explorer" || bad "testnet4 link wrong ($R4)"
R5=$(php -r "require '$ROOT/src/config.php'; echo basename(bathron_cache_path('x.json','testnet4')), ' ', basename(bathron_cache_path('x.json','mainnet'));")
[ "$R5" = "bathron_testnet4_x.json bathron_mainnet_x.json" ] && ok "cache path differs per network" || bad "cache path collision ($R5)"

echo "== no generated data tracked =="
if [ -d "$ROOT/.git" ]; then
  T=$(git -C "$ROOT" ls-files | grep -E "explorer-state/|genesis_burns.json|\.log$|backups/|\.env$" || true)
  [ -z "$T" ] && ok "git tracks no generated/secret files" || { echo "$T"; bad "generated/secret files tracked"; }
else
  ok "(no .git yet — skipped)"
fi

echo
[ "$FAIL" = 0 ] && echo "ALL TESTS PASS" || echo "TESTS FAILED"
exit $FAIL
