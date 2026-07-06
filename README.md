# SMSB Coding Test — Purchase Order Management

A Laravel API + Vue 3 SPA demo built for a covering four topics:

1. **Authentication & Roles** — public landing page, role-gated dashboard, role management, and an Administrator-only user management screen.
2. **Related CRUD** — Suppliers → Products → Purchase Orders → Purchase Order Items, with UUID primary keys, soft deletes, a JSON column, a boolean flag, PDF attachment upload (100–500 KB), searchable/sortable/filterable listings, and DB-backed select dropdowns.
3. **Audits** — every change to the Topic 2 entities is recorded (who, when, what changed) via an audit trail UI, while historical order line items are immutable even if the underlying product is edited later.
4. **Excel Export/Import** — dynamic column selection on export, header-matching on import, both processed via a queued background job.

## Stack

| Layer | Tech |
|---|---|
| Backend | Laravel 11 (PHP 8.3), Sanctum (SPA cookie auth), Spatie Permission, owen-it/laravel-auditing, Maatwebsite Excel |
| Frontend | Vue 3 + Vite, Pinia, Vue Router, PrimeVue (Aura theme) + Tailwind CSS, Axios |
| Database | MySQL 8.4 |
| Queue | Laravel's `database` queue driver, processed by a dedicated worker container |
| Package managers | Composer (PHP), pnpm (JS) |

Two independent projects, `laravel-api/` (API only) and `vue-app/` (SPA), talking over HTTP with Sanctum's SPA cookie authentication — no CORS/token dance required, just `withCredentials` + a shared `localhost` domain.

## Quick start (Docker — recommended)

Prerequisites: Docker Desktop or OrbStack (anything providing `docker` + `docker compose`), and `make` (optional but recommended — every `make` target is just a documented `docker compose` command, see the Makefile).

```bash
make up
# or, without make:
docker compose up -d --build
```

That single command:
- builds the Laravel API image and starts MySQL, the API, a queue worker, and the Vue dev server;
- waits for MySQL to be healthy before the API starts;
- runs migrations automatically on first boot;
- seeds demo data automatically **only** on a fresh database (safe to `docker compose restart` any time without duplicating data).

Once it's up:
- **Frontend:** http://localhost:5173
- **API:** http://localhost:8000
- **MySQL:** `127.0.0.1:3307` (user `smsb_user` / password `smsb_password` / db `smsb_coding_test`)

Seeded logins (password for both: `password`):
- `admin@example.com` — Administrator (full access, including User management)
- `staff@example.com` — Staff (everything except User management)

First boot takes a little longer (image build + `pnpm install` inside the `vue` container). Watch progress with `make logs`, or check `make ps` until everything shows healthy/running.

### Everyday commands

```bash
make ps            # container status
make logs          # tail every service
make logs-api      # tail just the Laravel API
make fresh         # wipe + re-migrate + re-seed demo data
make test          # run the backend test suite
make artisan cmd="queue:failed"   # run any artisan command
make down          # stop everything (keeps your data)
make clean         # stop everything AND delete volumes (mysql data, node_modules, uploads)
```

Run `make help` for the full list.

### Why a queue worker matters

Excel export/import (Topic 4) is deliberately implemented as a queued job, not inline. If the `queue` container isn't running, exports/imports will sit in `pending` forever — `make ps` should show it as `Up`, and `make logs-queue` should show jobs being picked up.

## Running without Docker

If you'd rather run things natively:

**Backend** (`laravel-api/`) — needs PHP 8.3+ with `pdo_mysql`, `bcmath`, `gd`, `zip`, `intl`, `exif` extensions, Composer, and a MySQL 8+ server:

```bash
cd laravel-api
composer install
cp .env.example .env      # already points at a local MySQL on 127.0.0.1:3307 — edit DB_* if yours differs
php artisan migrate --seed
php artisan storage:link
php artisan serve                 # http://localhost:8000
php artisan queue:work            # in a second terminal — required for export/import
```

**Frontend** (`vue-app/`) — needs Node 22+ and pnpm:

```bash
cd vue-app
pnpm install
pnpm run dev                      # http://localhost:5173
```

No `.env` edits are needed on the frontend — `VITE_API_BASE_URL` already defaults to `http://localhost:8000`.

## Running tests

```bash
make test
# or natively:
cd laravel-api && php artisan test
```

Tests run against an isolated in-memory SQLite database (configured in `phpunit.xml`) — they never touch your MySQL demo data. Coverage includes: Administrator-vs-Staff route gating, purchase-order line-item snapshot immutability after a product is edited, and audit-record creation on update.

## Project structure

```
.
├── docker-compose.yml     # orchestrates mysql + api + queue + vue
├── Makefile               # thin wrapper around docker compose
├── laravel-api/           # Laravel 11 API
│   ├── Dockerfile
│   ├── docker-entrypoint.sh   # waits for MySQL, migrates, seeds once, starts the server
│   ├── app/Http/Controllers/Api/
│   ├── app/Models/            # Supplier, Product, PurchaseOrder, PurchaseOrderItem, Export, Import…
│   ├── app/Exports, app/Imports, app/Jobs   # Topic 4: dynamic Excel export/import
│   ├── app/Support/ExportableModels.php     # registry of what's exportable/importable per entity
│   ├── database/migrations, database/seeders
│   └── tests/Feature/
└── vue-app/                # Vue 3 SPA
    └── src/
        ├── stores/auth.js          # Pinia auth store (Sanctum SPA login/logout)
        ├── router/index.js         # route guards (auth required / role required)
        ├── components/             # AppNav, AuditTrail, ExportDialog, ImportDialog
        └── views/                  # Landing, Login, Dashboard, Roles, Users, Suppliers, Products, PurchaseOrders
```

## Notable implementation details

- **Snapshot immutability (Topic 3):** `purchase_order_items` stores `product_name_snapshot` and `unit_price_snapshot`, copied from the product at the moment the line item is created. Editing a product afterward never touches existing order items — only the audit trail on the *product* shows the change happened.
- **Dynamic export/import (Topic 4):** `app/Support/ExportableModels.php` is the single source of truth for which columns each entity (suppliers, products, purchase-orders, users, roles) exposes to export, and how an uploaded file's header row maps back onto the model on import. Both directions run through `ProcessExport`/`ProcessImport` queued jobs, tracked in `exports`/`imports` tables so the frontend can poll status and show a download link or error summary.
- **Sanctum guard fix:** Laravel's `auth:sanctum` middleware temporarily switches the app's default auth guard to `sanctum` mid-request. Since `config/auth.php` didn't originally define a `sanctum` guard, Spatie Permission's guard-based model resolution broke for any relation query on `Role`/`Permission`. Fixed by explicitly registering a `sanctum` guard alongside `web`.
- **UUID-safe audits:** `owen-it/laravel-auditing`'s default migration assumes integer-keyed models (`$table->morphs('auditable')`). Since the Topic 2 models use UUID primary keys, this was changed to `$table->uuidMorphs('auditable')`.
- **`server.php` router in the Docker image:** the container runs `php -S 0.0.0.0:8000 server.php` rather than `php artisan serve` (which was unreliable connecting to MySQL from this Docker network) or `php -S ... public/index.php` (which looked correct but broke every static file under `public/storage` — completed Excel exports and uploaded PDF attachments came back as a 404 HTML page instead of the file, since passing `index.php` as the router makes PHP's built-in server run the full framework for *every* request). `laravel-api/server.php` is a copy of Laravel's own serve-command router, which checks for an on-disk file first and only falls through to the framework otherwise.
