# Coolify CI/CD — greyon-engine (Laravel)

Auto-deploy the API from GitHub when `main` updates.

**Repo:** `https://github.com/SophearaGit/greyon.git`  
**Suggested domain:** `https://engine.greyon.site`  
**SPA:** `https://greyon.site` (FRONTEND_URL)

## Architecture

```
push to main → Coolify (Nixpacks) → nginx + php-fpm + queue worker
                     ↘ GitHub Actions CI (pint + phpunit)
Post-deploy: php artisan migrate --force
```

---

## 1. Database (once)

Coolify → **New Resource** → **MySQL** (or Postgres).

Note the internal hostname Coolify shows (e.g. `mysql-xxxxx`). Use that as `DB_HOST` — not `127.0.0.1`.

Create database `greyon` (or match `DB_DATABASE`).

---

## 2. Create the Coolify application

1. Coolify → **New Resource** → **Application**
2. Connect GitHub for `SophearaGit/greyon` (engine repo)
3. Branch: **`main`**, enable **Auto Deploy**

### Build settings

| Setting | Value |
|---------|--------|
| Build Pack | **Nixpacks** |
| Ports Exposes | **80** |
| Base Directory | `/` |
| Install / Build / Start | leave empty (`nixpacks.toml` owns them) |
| Health check | `/up` (Laravel) |

### Post-deployment command

```bash
php artisan migrate --force --no-interaction
```

**Do not** put `db:seed` in post-deploy (re-seeds wipe/duplicate). Seed once manually:

```bash
php artisan db:seed --force
```

(Coolify → Application → **Execute Command**)

### Environment variables (runtime)

Copy from `.env.example` Coolify section. Minimum:

| Key | Example |
|-----|---------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://engine.greyon.site` |
| `APP_KEY` | `base64:...` (`php artisan key:generate --show`) |
| `FRONTEND_URL` | `https://greyon.site` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | Coolify MySQL hostname |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | `greyon` |
| `DB_USERNAME` | … |
| `DB_PASSWORD` | … |
| `SESSION_DRIVER` | `file` |
| `SESSION_SAME_SITE` | `none` |
| `SESSION_SECURE_COOKIE` | `true` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `info` |
| `QUEUE_CONNECTION` | `database` |
| `CACHE_STORE` | `file` |
| `FILESYSTEM_DISK` | `public` |
| `MAIL_MAILER` | `smtp` (when ready) |
| `GOOGLE_*` | production OAuth redirect → `https://engine.greyon.site/auth/google/callback` |

Optional: `RUN_MIGRATIONS=true` in start.sh — prefer Coolify **Post-deployment** instead.

### Domains

- Attach `engine.greyon.site` → HTTPS
- SPA must call this host; CORS uses `FRONTEND_URL`

---

## 3. GitHub Actions CI

Workflow: `.github/workflows/ci.yml`

- `composer install`
- `vendor/bin/pint --test`
- `php artisan test`

---

## 4. First deploy checklist

1. MySQL resource healthy; env `DB_*` correct
2. `APP_KEY` set
3. Deploy once → post-deploy migrate succeeds
4. Execute: `php artisan db:seed --force` (once)
5. Hit `https://engine.greyon.site/up`
6. From SPA: login as `dev@greyon.com.kh` / `password` (change after go-live)
7. Confirm cookies: Secure + SameSite=None across SPA ↔ API origins

---

## 5. Persistent storage (recommended)

Mount a volume for uploads if you use `storage/app/public`:

- Coolify Storage → mount `/app/storage/app` (or `/app/storage`)

`php artisan storage:link` already runs in `nixpacks.toml` start.sh.

---

## Rollback

Coolify → Deployments → redeploy previous good build.  
DB migrations are forward-only — keep migrations reversible when possible.
