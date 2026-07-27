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
