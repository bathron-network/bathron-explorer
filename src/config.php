<?php
/*
    src/config.php — BATHRON explorer shared RPC configuration loader.

    Secrets are NEVER stored in code. Configuration comes from, in order:
      1. real environment variables (systemd EnvironmentFile exports), then
      2. an env file OUTSIDE the webroot (default /etc/bathron/explorer-rpc.env,
         overridable via the BATHRON_RPC_CONFIG_FILE environment variable).

    Keys:
      BATHRON_RPC_HOST      (default 127.0.0.1)
      BATHRON_RPC_PORT      (default 27175)
      BATHRON_RPC_USER      (required)
      BATHRON_RPC_PASSWORD  (required; BATHRON_RPC_PASS accepted as legacy alias)
      BATHRON_DEX_URL       (optional; hides the DEX tab when empty)
      BATHRON_STATE_FILE    (optional; finality tracker state JSON)
      BATHRON_GENESIS_BURNS (optional; genesis burns JSON)

    Fail-closed: missing credentials -> generic maintenance page, no fallback,
    no sensitive defaults, no detail leaked to the client.
*/

function bathron_rpc_config_file() {
    $f = getenv('BATHRON_RPC_CONFIG_FILE');
    return ($f !== false && $f !== '') ? $f : '/etc/bathron/explorer-rpc.env';
}

function bathron_rpc_config() {
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    $cfg = false;
    $vals = [];
    $file = bathron_rpc_config_file();
    if (is_readable($file)) {
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            $eq = strpos($line, '=');
            if ($eq === false) continue;
            $k = trim(substr($line, 0, $eq));
            $v = trim(substr($line, $eq + 1));
            if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
                $v = substr($v, 1, -1);
            }
            $vals[$k] = $v;
        }
    }
    // Real environment variables take precedence over the file.
    foreach (['BATHRON_RPC_HOST','BATHRON_RPC_PORT','BATHRON_RPC_USER',
              'BATHRON_RPC_PASSWORD','BATHRON_RPC_PASS','BATHRON_DEX_URL',
              'BATHRON_STATE_FILE','BATHRON_GENESIS_BURNS'] as $k) {
        $e = getenv($k);
        if ($e !== false && $e !== '') $vals[$k] = $e;
    }
    $pass = $vals['BATHRON_RPC_PASSWORD'] ?? ($vals['BATHRON_RPC_PASS'] ?? '');
    if (($vals['BATHRON_RPC_USER'] ?? '') === '' || $pass === '') {
        return false;
    }
    $cfg = [
        'user' => $vals['BATHRON_RPC_USER'],
        'pass' => $pass,
        'host' => $vals['BATHRON_RPC_HOST'] ?? '127.0.0.1',
        'port' => (int)($vals['BATHRON_RPC_PORT'] ?? 27175),
        'dex_url' => $vals['BATHRON_DEX_URL'] ?? '',
        'state_file' => $vals['BATHRON_STATE_FILE'] ?? '',
        'genesis_burns' => $vals['BATHRON_GENESIS_BURNS'] ?? '',
        // --- deployment identity (see bathron_site_config) -------------------
        'network_label' => $vals['BATHRON_NETWORK_LABEL'] ?? '',
        'btc_source' => $vals['BITCOIN_SOURCE_NETWORK'] ?? '',
        'btc_explorer_base' => $vals['BITCOIN_EXPLORER_BASE'] ?? '',
        'funding_address' => $vals['MEASUREMENT_FUNDING_ADDRESS'] ?? '',
        'funding_target_sats' => (int)($vals['MEASUREMENT_FUNDING_TARGET_SATS'] ?? 0),
        'cache_dir' => $vals['BATHRON_CACHE_DIR'] ?? '',
    ];
    return $cfg;
}

/*
    Deployment identity — lets ONE codebase serve several BATHRON chains without
    ever mixing their state. Every value is configuration; nothing about a
    Bitcoin network is hardcoded here.

      BATHRON_NETWORK_LABEL   banner text ('' = no banner, the default public
                              deployment)
      BITCOIN_SOURCE_NETWORK  the Bitcoin network this chain reads as its
                              monetary source. AUTHORITATIVE VALUE comes from the
                              BATHRON RPC (getbtcsyncstatus.network, which is
                              consensus-committed); this variable is only the
                              value shown before/without RPC, and there is NO
                              hardcoded fallback to any network.
      BITCOIN_EXPLORER_BASE   external Bitcoin explorer used ONLY for
                              cross-checking links (never as a data source)
      MEASUREMENT_FUNDING_*   public receive address + target, funding panel is
                              hidden when the address is empty
      BATHRON_CACHE_DIR       per-deployment cache directory. Two deployments
                              MUST NOT share it (that would mix chain state).
*/
function bathron_site_config() {
    static $s = null;
    if ($s !== null) return $s;
    $c = bathron_rpc_config();
    $s = [
        'network_label' => is_array($c) ? ($c['network_label'] ?? '') : '',
        'btc_source' => is_array($c) ? ($c['btc_source'] ?? '') : '',
        'btc_explorer_base' => is_array($c) ? ($c['btc_explorer_base'] ?? '') : '',
        'funding_address' => is_array($c) ? ($c['funding_address'] ?? '') : '',
        'funding_target_sats' => is_array($c) ? ($c['funding_target_sats'] ?? 0) : 0,
        'cache_dir' => is_array($c) ? ($c['cache_dir'] ?? '') : '',
    ];
    return $s;
}

/*
    External Bitcoin explorer base URL for cross-check links.
    Derived from the network the NODE reports, so a link can never point at a
    different chain than the one the consensus tracks. Unknown network => no
    link at all (fail closed rather than guess).
*/
function bathron_btc_explorer_base($network) {
    $s = bathron_site_config();
    if ($s['btc_explorer_base'] !== '') return rtrim($s['btc_explorer_base'], '/');
    switch (strtolower((string)$network)) {
        case 'testnet4': return 'https://mempool.space/testnet4';
        case 'mainnet':
        case 'main':     return 'https://mempool.space';
        default:         return '';   // unknown network: no link, no guess
    }
}

/*
    Per-deployment cache path. Falls back to a network-namespaced file in the
    system temp dir so that two deployments on one host still never collide.
*/
function bathron_cache_path($name, $network = '') {
    $s = bathron_site_config();
    $ns = preg_replace('/[^a-z0-9_-]/i', '', (string)($network !== '' ? $network : $s['btc_source']));
    if ($ns === '') $ns = 'unknown';
    $dir = $s['cache_dir'] !== '' ? rtrim($s['cache_dir'], '/') : sys_get_temp_dir();
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    return $dir . '/bathron_' . $ns . '_' . preg_replace('/[^a-z0-9_.-]/i', '', $name);
}

function bathron_rpc_unavailable() {
    http_response_code(503);
    header('Retry-After: 60');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<meta http-equiv="refresh" content="60"><title>BATHRON Explorer</title></head>'
       . '<body style="font-family:sans-serif;background:#0d1117;color:#e6edf3;'
       . 'display:flex;align-items:center;justify-content:center;min-height:100vh">'
       . '<p>Explorer data temporarily unavailable. Please try again shortly.</p>'
       . '</body></html>';
    exit;
}
