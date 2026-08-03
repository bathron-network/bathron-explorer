# Security policy

The BATHRON explorer is an **experimental public-testnet** component. It is
designed to hold no secrets in code and to fail closed, but it has not been
audited.

## Reporting a vulnerability

Email **security@bathron.org**. Please do NOT open a public issue for
security-sensitive reports.

Include: affected file/endpoint, reproduction steps, impact assessment.
You should receive an acknowledgement within a few days.

## Scope notes

- The explorer reads a local BATHRON node over loopback RPC with a dedicated
  low-privilege credential; it must never be configured with a node's primary
  RPC credentials.
- The web server must serve only `public/`. `src/`, `tracker/`, state files
  and the environment file live outside the webroot.

## Deployment rules

**The PHP-FPM pool must NOT run as the user that owns node data.** The
explorer needs only its webroot plus its own env file. If the pool runs as the
account owning wallets, datadirs, RPC cookies or operator keys, any file-read
bug in the application becomes a key compromise. Run the pool as a dedicated
low-privilege user (e.g. `www-data`), keep the env file `0640 root:<pool-user>`
and leave every wallet/datadir `0700` to its own owner. Verify with:
`sudo -u <pool-user> test -r <secret> && echo READABLE`.

**The explorer holds no private key of any kind.** It never talks to a wallet
RPC, never signs, never spends. A funding panel, when configured, shows a
*public receive address*, a balance read from a public block explorer, and a
cross-check link — nothing else.

**One deployment = one chain.** A single codebase can serve several BATHRON
chains, but each deployment must have its own env file, its own
`BATHRON_CACHE_DIR` and its own RPC endpoint. Sharing a cache directory
between two chains mixes their state and can display one chain's data as the
other's.

**No Bitcoin network is hardcoded.** The source network is read from the node
(`getbtcsyncstatus.network`, committed by the chain at genesis). An unknown
network yields a neutral label and *no* external link rather than a guess —
mislabelling a disposable chain as a monetary one is a security bug, not a
cosmetic one. External block explorers are cross-check links only, never a
data source.
