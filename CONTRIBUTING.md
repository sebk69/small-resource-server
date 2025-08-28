# Contributing to Small Resource Server

Thanks for considering a contribution! This document explains how to set up your environment, submit changes, and keep the project healthy.

Repository: **github.com:sebk69/small-resource-server.git**

- SSH: `git clone git@github.com:sebk69/small-resource-server.git`
- HTTPS: `git clone https://github.com/sebk69/small-resource-server.git`

---

## Table of contents
- [Code of Conduct](#code-of-conduct)
- [Quick Start (TL;DR)](#quick-start-tldr)
- [Prerequisites](#prerequisites)
- [Project layout](#project-layout)
- [Local development](#local-development)
- [Tests](#tests)
- [Coding standards](#coding-standards)
- [Commit messages & branches](#commit-messages--branches)
- [Submitting a Pull Request](#submitting-a-pull-request)
- [Reporting bugs & requesting features](#reporting-bugs--requesting-features)
- [Security](#security)
- [Release notes / Changelog](#release-notes--changelog)

---

## Code of Conduct
Be kind, constructive, and respectful. By participating, you agree to uphold a friendly, harassment‑free experience for everyone.

---

## Quick Start (TL;DR)
```bash
# 1) Clone
git clone git@github.com:sebk69/small-resource-server.git
cd small-resource-server

# 2) Install dependencies
composer install

# 3) Run tests (Pest)
./vendor/bin/pest

# 4) Run the HTTP server (dev)
php bin/http
```

---

## Prerequisites

- **PHP 8.4+**
- **Swoole** PHP extension
- **Composer**
- **ext-json**
- **MySQL** (for persistence tested by the infra layer; unit tests use fakes)
- (Optional) **Supervisor** or a process manager for prod

> Some tests are Swoole‑aware and will **skip** when Swoole is not loaded.

---

## Project layout

```
src/
  domain/
    Application/
      Entity/
      Exception/
      UseCase/
    InterfaceAdapter/
      Gateway/
        Manager/
        UseCase/
  infrastructure/
    Actions/              # HTTP handlers (Swoole)
    Http/                 # Router & exceptions
    Orm/                  # Resource manager (MySQL)
    Kernel.php            # Bootstraps services and shared tables
vendor/small/swoole-patterns/src/Manager/StoredListManager/UnifiedTableStoredListManager.php
tests/                   # Pest test suite (unit & light integration)
bin/http                 # Entry point for the Swoole HTTP server
```

Key idea: **UnifiedTableStoredListManager** uses **one shared Swoole\Table** for *all* lists. In master, call:
```php
UnifiedTableStoredListManager::masterInit($rows);
```
(Handled during kernel boot) so all workers share the same memory segment.

---

## Local development

### 1) Environment variables

When running under **Supervisor**, set env vars in the program block or use a wrapper script to source a file:

```ini
[program:swoole-http]
command=/app/bin/http
directory=/app
user=www-data
autostart=true
autorestart=true
stdout_logfile=/var/log/supervisor/swoole-http.log
startsecs=10
environment=MYSQL_HOST="database",MYSQL_USER="root",MYSQL_PASSWORD="secret",RESOURCE_READ="abcd",RESOURCE_READ_LOCK="uvwx",RESOURCE_WRITE="afgh"
```

For local dev, you can export variables in your shell or load from a `.env` file using a dotenv loader if you prefer.

### 2) Running the server
```bash
php bin/http
# or under supervisor/systemd/docker, as appropriate
```

### 3) Common pitfalls
- Ensure `daemonize=false` in Swoole settings (the process manager handles daemonization).
- If binding to **80/443** as non‑root, give PHP the capability:
  `sudo setcap 'cap_net_bind_service=+ep' $(command -v php)`
  or run behind a reverse proxy on a high port (e.g., 9501).
- Confirm the Swoole extension is loaded: `php -m | grep -i swoole`.

---

## Tests

The project uses **Pest** (on top of PHPUnit).

Install and run:
```bash
composer require --dev pestphp/pest --with-all-dependencies
./vendor/bin/pest
```

Coverage:
```bash
./vendor/bin/pest --coverage
```

Notes:
- Tests that depend on Swoole will **skip** automatically when the extension is missing.
- When you add a new class, please add/extend tests under `tests/`.
- For Swoole‑dependent code, prefer **fakes/mocks** to avoid hitting the network or MySQL.

---

## Coding standards

- Follow **PSR‑12** style (php-cs-fixer or phpcs are welcome, but not strictly required).
- Use **strict types** where reasonable: `declare(strict_types=1);`
- Prefer **constructor injection** and **small, focused classes**.
- Keep public APIs typed and documented; prefer domain exceptions over generic ones.
- For list storage, keep key formats consistent (`s:{name}`, `i:{name}:{index}`).
- Tests should be deterministic and isolated (no global state leakage).

---

## Commit messages & branches

- Use clear, descriptive messages.
- Conventional Commits are welcome (e.g., `feat:`, `fix:`, `docs:`, `test:`), but not mandatory.
- Create a **feature branch** off `main`:
  ```bash
  git checkout -b feat/better-locks
  ```

---

## Submitting a Pull Request

1. **Open an issue** first for large changes to discuss direction.
2. **Write tests** for new behavior or bugfixes.
3. Ensure `./vendor/bin/pest` passes locally.
4. Update **README** or docs if behavior/usage changes.
5. Open a PR against **`main`**:
    - Include a clear title and description.
    - Link the issue it closes (e.g., `Closes #42`).

### PR Checklist
- [ ] Tests added/updated
- [ ] All tests pass
- [ ] No breaking public API changes (or documented)
- [ ] Docs updated (README/CHANGELOG if needed)

---

## Reporting bugs & requesting features

Use **GitHub Issues** with a minimal reproduction:
- What you expected vs what happened
- Exact request/route, headers (`x-api-key`, `x-ticket`) and query (`lock=1|0`)
- Logs (Supervisor + `/var/log/supervisor/swoole-http.log`)
- Environment details (PHP/Swoole versions, OS, Docker?)

---

## Security

Please avoid posting sensitive details in public issues.
Use GitHub’s **private vulnerability reporting** if enabled for the repo, or open an issue requesting a secure channel to disclose.

---

## Release notes / Changelog

Add human‑readable notes in PR descriptions. If changes are user‑visible, consider updating a `CHANGELOG.md` (if present) or the README.

---

Thanks again for contributing ❤️