# Upstream provenance

This explorer is a derivative work. This file pins the exact upstream it
derives from, verifiably.

## Upstream

| | |
|---|---|
| Project | **RPC Ace** (RPC AnyCoin Explorer) |
| Author | Robin Leffmann `<djinn at stolendata dot net>` |
| URL | https://github.com/stolendata/rpc-ace/ |
| Version | **v0.8.0** (`ACE_VERSION = '0.8.0'`, © 2014–2017) |
| Pinned commit | `183cbb957784260410059b9b53247b9f23ad8bdf` (branch `master`, head at pin time, 2026-07-27) |
| Verifiable archive | `https://codeload.github.com/stolendata/rpc-ace/tar.gz/183cbb957784260410059b9b53247b9f23ad8bdf` |
| Archive SHA-256 | `08596ca6222d31679dc6dc0fa2a5c699d8821ea25fa51cf199a5e3c1cf964e7a` |
| Upstream license (verbatim header) | `licensed under CC BY-NC-SA 4.0 - https://creativecommons.org/licenses/by-nc-sa/4.0/` |

To re-verify: download the archive URL above and compare its SHA-256.

## Derivation map

| File here | Derives from | Nature |
|---|---|---|
| `public/index.php` | `rpcace.php` (upstream) | **Derivative.** Started from RPC Ace's RPC-driven explorer pattern; since then extensively rewritten for BATHRON (settlement supply/invariants, finality panel, operators, SPV state, env-file configuration, fail-closed error page). The CC BY-NC-SA 4.0 license carries over via ShareAlike. |
| `tracker/finality_tracker.php` | — (original) | Written for BATHRON; licensed CC BY-NC-SA 4.0 with the repo for consistency. |
| `src/config.php` | — (original) | Written for BATHRON; licensed CC BY-NC-SA 4.0 with the repo for consistency. |
| `src/easybitcoin.php` | [EasyBitcoin-PHP](https://github.com/aceat64/EasyBitcoin-PHP) by Andrew LeCody | **Bundled third-party dependency, MIT** (notice preserved in the file and in `licenses/easybitcoin-MIT.txt`). Not part of upstream RPC Ace's repository — upstream instructs sourcing it separately, as here. |
| `public/*.png`, docs, tests, systemd unit | — (original) | BATHRON assets/infrastructure. |

## Licensing consequence

Because the main file derives from a CC BY-NC-SA 4.0 work, the ShareAlike
condition applies: this repository is published under **CC BY-NC-SA 4.0**.

Creative Commons itself recommends against using CC licenses for software
(see the [CC FAQ](https://creativecommons.org/faq/)); the license here is
**inherited**, not chosen. This makes the explorer **source-available** —
NOT open source under the OSI definition (the NonCommercial clause fails
OSD #6). Escaping this would require a clean-room rewrite of the explorer,
which is a possible future decision of the project.
