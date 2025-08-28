# Small Swoole Resource Server

<img src="img/tests-badge.png" width="200px">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="img/coverage-badge.png" width="200">

A tiny, fast HTTP API built on **Swoole** to read, lock, update and unlock named resources.
It uses a **single, shared Swoole\Table** to coordinate tickets/locks across workers (via
`UnifiedTableStoredListManager`) and a lightweight domain layer (Clean Architecture flavored).

> PHP **8.4+** required (project uses property hooks / asymmetric visibility),
> and the **Swoole** extension.

---

## ✨ Features

- **Resource queue/lock** with tickets (`x-ticket` header), safe across Swoole workers
- **Single shared table** for all lists (`UnifiedTableStoredListManager::masterInit`)
- **HTTP API**: create resource, fetch (optionally lock), update, unlock
- **Auth by API key** (`x-api-key`) with roles: READ / LOCK / WRITE
- **MySQL storage** for metadata via `small/swoole-entity-manager-core`
- **Pest** unit tests included

---

## 📦 Requirements

- PHP 8.4+
- ext-swoole
- ext-json
- MySQL (server accessible)
- Composer

---

## 🚀 Quick start

Install dependencies:

```bash
composer install
```

Run the HTTP server (from project root):

```bash
php bin/http
```

Or under **Supervisor** (recommended for prod):

```ini
[program:swoole-http]
command=/app/bin/http
directory=/app
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/swoole-http.log
startsecs=10
; set your env here or use a wrapper
environment=MYSQL_HOST="database",MYSQL_USER="root",MYSQL_PASSWORD="secret",RESOURCE_READ="abcd",RESOURCE_READ_LOCK="uvwx",RESOURCE_WRITE="afgh"
```

> **Note:** `UnifiedTableStoredListManager::masterInit()` is called during Kernel
> boot **before** worker fork so all workers share the same memory segment.

---

## ⚙️ Configuration

The app reads configuration from environment variables (via `Small\Env\Env`)
and passes them into DB/authorization layers.

**Database**
- `MYSQL_HOST` (default `127.0.0.1`)
- `MYSQL_PORT` (default `3306`)
- `MYSQL_DATABASE` (default `small_swoole_resource`)
- `MYSQL_USER` (default `root`)
- `MYSQL_PASSWORD` (default empty)

**API keys / roles**
- `RESOURCE_READ` → allows reading (`READ`)
- `RESOURCE_READ_LOCK` → reading + locking (`READ`,`LOCK`)
- `RESOURCE_WRITE` → full access (`READ`,`LOCK`,`WRITE`)

Provide the selected key in header `x-api-key`.

**Ticket propagation**
- Client receives ticket in `x-ticket` header from **GET**.
- Client must send same `x-ticket` when **PUT** / **unlock**.

---

## 🌐 HTTP API

All endpoints under the same host/port exposed by Swoole HTTP.

### Create resource

```
POST /resource
Headers:
  x-api-key: <WRITE key>

Body (JSON):
{
  "name": "printer",
  "timeout": 300            // optional server-side semantics
}

201 Created
Content-Type: application/json
{ ...resource... }
```

### Get resource data (with optional lock)

```
GET /resource/{resourceName}/{selector}?lock=1
Headers:
  x-api-key: <READ or READ+LOCK>
  x-ticket: <existing ticket | optional>

Query:
  lock=1  (default)  → attempt to acquire/keep lock
  lock=0             → read without locking

Responses:
- 200 OK + JSON body     → resource data is available
  + Header: x-ticket: <ticket>
- 202 Accepted           → not available yet (locked by someone else),
  body: { "unavailable": true }
  + Header: x-ticket: <ticket> (your ticket to retry with)
- 404 Not Found          → resource/selector unknown
- 401 Unauthorized       → missing/invalid x-api-key / missing LOCK when lock=1
```

### Update resource data

```
PUT /resource/{resourceName}/{selector}
Headers:
  x-api-key: <WRITE key>
  x-ticket: <ticket from GET>

Body: raw JSON payload to store

204 No Content
```

### Unlock resource

```
POST /resource/{resourceName}/{selector}/unlock
Headers:
  x-api-key: <READ or READ+LOCK>
  x-ticket: <ticket>

200 OK
{ "unlocked": true }
```

---

## 🧠 Concurrency model

This project uses **one** shared `Swoole\Table` for **all** resource lists.

- Call `UnifiedTableStoredListManager::masterInit($rows)` **once in master** (Kernel does it).
- Each list stores its state (`left/right/count`) as a state row `s:{name}`
  and its items as `i:{name}:{index}` with columns `name`, `data`.
- Workers share the same table after fork → immediate visibility across workers.

---

## 🗂️ Project structure (key parts)

```
src/
  domain/
    Application/
      UseCase/
        GetResourceDataUseCase.php
        LockResourceDataUseCase.php
        UpdateResourceDataUseCase.php
        UnlockResourceDataUseCase.php
    ...
  infrastructure/
    Actions/
      ResourceCreateAction.php
      ResourceGetAction.php
      ResourceUpdateAction.php
      ResourceUnlockAction.php
    Http/Router.php
    Kernel.php
vendor/small/swoole-patterns/src/Manager/StoredListManager/UnifiedTableStoredListManager.php
```

---

## 🔐 Authentication & roles

- **Action base class** parses:
    - `x-api-key` → determines `READ` / `LOCK` / `WRITE` rights.
    - `x-ticket` → stored on the Action and passed to UseCases.
- `ResourceGetAction` reads **`?lock=1|0`** (default `1`) and enforces `LOCK` permission when `=1`.

---

## 🧪 Testing

Pest is set up. Some tests are **Swoole-aware** and will be **skipped** when the
extension is not loaded.

Install and run:

```bash
composer require --dev pestphp/pest --with-all-dependencies
composer test
# or:
./vendor/bin/pest
```

Coverage:

```bash
./vendor/bin/pest --coverage
```

---

## 🐳 Docker / Supervisor tips

- Supervisor doesn’t read shell rc files; pass env via `environment=` or a wrapper that sources a file.
- Ensure `daemonize` is **false** in Swoole settings (Supervisor is the daemon).

---

## 📄 License

MIT — see [`LICENCE`](./LICENCE).

© 2025 Sébastien Kus.