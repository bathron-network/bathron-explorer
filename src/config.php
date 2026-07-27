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
    ];
    return $cfg;
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
