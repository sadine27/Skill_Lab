# Tests

Automated, dependency-light tests for the Smart Waste Aggregation System.
They boot the PHP built-in server against an **ephemeral SQLite database**
(no MySQL server required) and drive it over real HTTP with `curl`.

## Run

```bash
./tests/smoke_test.sh   # functional happy-path (32 checks)
./tests/pentest.sh      # security / abuse cases  (31 checks)
```

Both exit non-zero if any check fails, so they drop straight into CI.
Requires `php` (with the `pdo_sqlite` extension, bundled by default) and `curl`.

## smoke_test.sh — functional

| Area | Checks |
|------|--------|
| Registration | form renders, account created, duplicate email rejected, short password rejected |
| Login | form renders, wrong password rejected, correct login per role with the right redirect |
| Dashboards | citizen / collector / admin dashboards render when authenticated |
| Stats API | `api/stats.php` returns JSON counts |
| Lifecycle | citizen submits → admin assigns (status → in_progress) → collector marks collected → admin sees it |
| Logout | session destroyed, redirect to login |

## pentest.sh — security

| Attack | Expected defence |
|--------|------------------|
| Auth bypass | unauthenticated GET/POST on protected pages → redirect to login |
| Vertical priv-esc | wrong-role access (citizen↔collector↔admin) → **403** |
| Role tampering | self-registering with `role=admin` is silently downgraded to citizen |
| SQL injection | tautology / comment payloads in login → rejected (prepared statements) |
| Stored XSS | `<script>` in a report description is rendered HTML-escaped |
| IDOR | a collector cannot see or mutate a report assigned to another collector |
| Invalid input | non-whitelisted status value is ignored |
| Disguised upload | a `.php` file sent as `image/jpeg` is rejected by content inspection |
| Info leak | public stats API exposes no emails or password hashes |

## How it works without MySQL

`db.php` reads an optional `WS_DB_DSN` env var (plus `WS_DB_USER` /
`WS_DB_PASS`). When unset it falls back to the original XAMPP MySQL config, so
production behaviour is unchanged. `tests/lib.sh` sets
`WS_DB_DSN=sqlite:/tmp/...` and `tests/seed_sqlite.php` seeds known accounts
(`citizen@`, `collector@`, `collector2@`, `admin@test.local`, password
`pass123`).

To run against a **real MySQL** instead, import `schema.sql`, create a few
accounts, and point `curl` at your XAMPP URL — the same flows apply.

## Manual checks still needed (real browser)

- [ ] **Photo upload** — submit a report with a real JPG/PNG and confirm it
      saves under `uploads/` and displays. (The disguised-upload rejection is
      covered automatically; a real successful upload still wants a human eye.)
- [ ] **Cross-browser / mobile** end-to-end run on the deployed server.
- [ ] **Production DB** — import `schema.sql`, update `db.php` credentials.
