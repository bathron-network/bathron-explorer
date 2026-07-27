# BATHRON Explorer

> **Status: experimental public-testnet explorer.** A working demonstrator for
> the BATHRON public testnet. No mainnet exists. No availability guarantee.
> Interfaces may change without notice.

Lightweight PHP block explorer for the [BATHRON](https://bathron.org) network:
chain tip, blocks, settlement supply and invariants (A5/A6/A7), finality
(quorum, per-block delay), operators, in-consensus Bitcoin SPV state.

All data is read from a **local BATHRON node** over RPC — the explorer serves
nothing it cannot verify against its own node.

Canonical documentation: **https://bathron.org/docs/**

## Layout

```
public/     webroot (index.php + assets) — point your web server HERE only
src/        shared code (config loader, RPC client) — outside the webroot
tracker/    finality_tracker.php — daemon feeding the finality panel
contrib/systemd/  example unit for the tracker
tests/      configuration & anti-secret checks
```

## Requirements

- PHP 8.x with `curl` (php-fpm or `php -S` behind a reverse proxy)
- A synced BATHRON node on the same machine (`bathrond -testnet`) with RPC
  enabled on loopback

## Configuration — no secrets in code, ever

The explorer and the tracker read the same configuration, in order:

1. real environment variables (e.g. a systemd `EnvironmentFile`), then
2. an env file **outside the webroot** — default `/etc/bathron/explorer-rpc.env`,
   overridable with `BATHRON_RPC_CONFIG_FILE`.

Copy `.env.example`, fill in your values, and install it root-owned:

```bash
sudo install -m 0640 -o root -g www-data .env.example /etc/bathron/explorer-rpc.env
sudo edit /etc/bathron/explorer-rpc.env
```

Use a **dedicated RPC credential** for the explorer (`rpcauth=` in
`bathron.conf`), never your node's primary credentials.

If the configuration is missing or incomplete the explorer **fails closed**
with a generic "data temporarily unavailable" page: no fallback, no default
password, no connection detail ever reaches the browser.

## Run

```bash
# web UI: serve public/ (example with the PHP built-in server behind a proxy)
php -S 127.0.0.1:3001 -t public/

# finality tracker (or install the systemd unit from contrib/systemd/)
php tracker/finality_tracker.php
```

The tracker writes its state JSON to `<repo>/explorer-state/finality.json` by
default; override with `BATHRON_STATE_FILE`.

## Tests

```bash
tests/run_tests.sh
```

Checks: PHP lint, fail-closed behavior without credentials, config parsing,
and the anti-secret / anti-server-path guards (no credentials, no `/home/...`
paths, no operator IPs, no generated data tracked by git).

## Transport note

The reference public instance is currently served over **plain HTTP** (an
experimental testnet demonstrator — it displays public chain data and holds
no secrets). Do not treat it as a trusted endpoint. An HTTPS migration
(reverse proxy + certificate) is planned as a separate operational change;
self-hosters are encouraged to front `public/` with TLS from day one.

## Security

Please report vulnerabilities to **security@bathron.org** — see
[SECURITY.md](SECURITY.md). Do not open public issues for security reports.

## License — source-available (CC BY-NC-SA 4.0)

This explorer derives from [RPC Ace](https://github.com/stolendata/rpc-ace/)
v0.8.0 by Robin Leffmann, licensed **CC BY-NC-SA 4.0**; the ShareAlike terms
carry over, so this repository is published under the same license. That makes
it **source-available, not open source** in the OSI sense (the NonCommercial
clause restricts commercial use), and the license is inherited rather than
chosen — Creative Commons itself advises against CC licenses for software.

- Explorer (`public/index.php`, `tracker/`, `src/config.php`): CC BY-NC-SA 4.0
- `src/easybitcoin.php`: **MIT** (third-party, notice preserved in the file
  and in [licenses/](licenses/))

Full upstream pin (exact commit, verifiable archive SHA-256, per-file
derivation map): **[PROVENANCE-UPSTREAM.md](PROVENANCE-UPSTREAM.md)**.
See [LICENSE](LICENSE).
