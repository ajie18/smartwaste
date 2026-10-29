# SmartWaste — Laravel Port

Full conversion of the SmartWaste waste-management platform (Node/Express +
partial Laravel hybrid) into a single Laravel application: admin panel,
driver portal, resident portal, and now real-time push via Laravel Reverb.

## New: resident ↔ driver chat + AI analytics

**Chat** (`app/Models/ChatConversation`, `ChatMessage`;
`app/Http/Controllers/Chat/ChatController`; `public/js/chat-widget.js`):

- One thread per resident/driver pair (`chat_conversations` has a unique
  constraint on the pair), reused across pickups rather than spawning a
  new thread every day.
- `POST /api/resident/chat/start` finds/creates the thread with whoever's
  driving today's route — the resident dashboard calls this automatically
  on load.
- Driver side sees a conversation list (`GET /api/chat/conversations`);
  resident side skips straight to the thread if there's exactly one.
- Real-time via the same Reverb setup as everything else:
  `conversation.{id}` (both participants, while the thread's open) plus a
  ping on the recipient's personal channel (`driver.{id}` / `resident.{id}`)
  so a badge shows up even if they're not looking at the thread.
  `routes/channels.php` authorizes both.
- `public/js/chat-widget.js` is a single self-contained file (floating
  button + panel, own injected CSS) — it doesn't touch `dashboard.js` in
  either portal at all, just gets initialized from a small inline script
  at the bottom of each dashboard view. Falls back to 5s polling if Echo
  isn't connected.
- Closed the previous "resident has no Echo bridge" gap as a side effect —
  `public/js/resident/echo-bridge.js` is new, and also makes truck-location
  and new-announcement arrive instantly instead of waiting for the next
  poll tick.

**AI Insights** (`App\Services\AiInsightService`;
`Admin\AiInsightController`): wraps the Anthropic Messages API.

- `GET /api/admin/ai/analytics-insight` — gathers the same report/pickup/
  fleet numbers the Analytics page already shows, asks Claude for a
  ~150-word plain-English summary (trends, what needs attention, 2-3
  recommendations). Wired into a new "AI Insights" panel at the top of
  `admin/analytics.blade.php` with a "Generate Insight" button.
- `POST /api/admin/ai/reports/{id}/triage` — given a report's type +
  description, asks Claude for a suggested urgency level (low/medium/high)
  and a one-line action note. **Now wired into the Reports page** — the
  "Full Details" modal (`viewReport()` in `reports.js`) has a
  "✨ Suggest urgency & next step" button that calls this and renders the
  result inline. Syntax-checked after editing since I don't have full
  visibility into everything else `reports.js` does.
- Both degrade gracefully with no `ANTHROPIC_API_KEY` set — returns a
  clear "not configured" message/JSON instead of a 500.

## Setup

**This zip is now a complete, self-contained Laravel 11 project** — the
framework skeleton (`artisan`, `public/index.php`, `bootstrap/providers.php`,
all default `config/*.php` files) plus the custom SmartWaste app layer on
top of it. Earlier drops of this zip were missing the skeleton entirely;
that's fixed now.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link   # needed for announcement image uploads to resolve
```

**No `npm install` or Node needed at all** — every page uses plain
`public/css`/`public/js` assets or CDN scripts (Chart.js, Leaflet,
pusher-js, laravel-echo). There's no Vite build step anywhere in these
views.

Then:

```bash
php artisan serve
```

and visit `http://localhost:8000/login`. For real-time (optional but
wired up):

```bash
php artisan reverb:start
```

## Deploy on Vercel

The application uses plain CSS and JavaScript files under `public/`; it does
not use Vite or an npm build. The checked-in `vercel.json` selects the
community `vercel-php` runtime and overrides any dashboard build command
such as `vite build`. Its build script stages safe static files from
`public/` into `dist/`, excluding PHP files and `.htaccess`, while Laravel
requests are handled by `api/index.php`.

Configure these Vercel Production environment variables:

```text
APP_ENV=production
APP_DEBUG=false
APP_KEY=<output of php artisan key:generate --show>
APP_URL=https://<your-vercel-domain>
DB_CONNECTION=mysql
DB_HOST=<managed-database-host>
DB_PORT=<managed-database-port>
DB_DATABASE=<database-name>
DB_USERNAME=<database-user>
DB_PASSWORD=<database-password>
SESSION_DRIVER=cookie
CACHE_STORE=array
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
```

Use a managed external database; Vercel's function filesystem and the
repository's SQLite file are not persistent database storage. Run
`php artisan migrate --force` against the configured database from a trusted
environment before using the deployment. The Vercel config uses cookie
sessions, in-memory cache, and `/tmp` for compiled views so requests do not
depend on writable persistent storage. For Aiven MySQL, include its CA
certificate in the deployment and set `MYSQL_ATTR_SSL_CA` to the certificate
path if TLS verification requires it. Do not commit database credentials or
the `.env` file.

## Deploy on Railway

This repository includes a `Dockerfile` and `railway.json`. In Railway,
create a project, deploy this repository, and add a MySQL service. Railway
will build the PHP/Apache image, serve Laravel from `public/`, run the
migrations, and expose the `/up` health check.

Add these variables to the Laravel service. The `MYSQL*` values can be
copied from the linked Railway MySQL service, or use Railway's variable
references in the dashboard:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<your-railway-domain>
APP_KEY=<output of php artisan key:generate --show>
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log
```

Generate `APP_KEY` locally with `php artisan key:generate --show`; do not
commit `.env`. Set `SANCTUM_STATEFUL_DOMAINS` to the Railway hostname if
you later use Sanctum's cookie authentication. Uploaded announcement
images are stored in the container filesystem by default, so attach a
Railway volume mounted at `/var/www/html/storage/app/public` when those
uploads must survive redeploys.

Reverb is a separate long-running process and should be deployed as a
second Railway service from this repository with the start command
`php artisan reverb:start --host=0.0.0.0 --port=$PORT`. Keep its
`REVERB_APP_*` values aligned with the web service and set the browser
client's public Reverb host to that service's public domain. The web
service can run with `BROADCAST_CONNECTION=log` if real-time updates are
not needed.

**Defaults are deliberately zero-setup**: `SESSION_DRIVER`, `CACHE_STORE`
are `file` and `QUEUE_CONNECTION` is `sync` in `.env.example` — no extra
`sessions`/`cache`/`jobs` tables needed. The only required external
service is MySQL (`DB_*` in `.env`). Everything else (SMS, Reverb) degrades
gracefully — `SmsService` logs instead of sending when `IPROG_API_TOKEN`
is unset, and the driver dashboard just stays on REST polling if Reverb
isn't running.

## What's here

- **Models / Migrations / Controllers** — see prior notes; unchanged this
  round except where noted below.
- **Auth pages** (`resources/views/auth/*.blade.php`) — **these are your
  real `login.html` / `register.html` / `forgot-password.html` /
  `verify-email.html`**, converted mechanically (asset paths → `asset()`,
  internal links → `route()`). The JS (`public/js/{login,register,
forgot-password,verify-email}.js`) is copied over unmodified — it already
  talks to `/api/auth/*`, which matches `routes/api.php` exactly.
- **`App\Services\SmsService`** — rewritten to match your real `sms.js`
  (IPROG SMS API), not the earlier Semaphore guess. `normalizePhone()`,
  `sendOTP()`, and `sendPickupNotice()` are direct ports. Config key is now
  `IPROG_API_TOKEN` (see `.env.example`).
- **`Admin\PickupController::notifyPickupToday()`** — now actually sends
  SMS via `SmsService::sendPickupNotice()` to every resident with a phone
  on file, instead of being a TODO stub.
- **`Admin\AnnouncementController::store()`** — now broadcasts
  `new-announcement` on the `reports` channel when an announcement is
  published.
- **`Resident\ResidentController::createReport()`** — now broadcasts
  `new-resident-report` on the `reports` channel when a resident files a
  report, so it shows up live on driver dashboards.
- **CSS** — `public/css/admin/*.css`, `public/css/driver/dashboard.css`,
  `public/css/resident/dashboard.css` are all in now. **`style.css` (the
  base stylesheet with the `--panel`/`--gold`/`--border2`/etc. variables
  every page depends on) and `css/auth.css` were not part of any upload —
  the app will render unstyled until those are added** to `public/css/`.
- **`public/js/style.js`** — the real shared JS (theme toggle, sidebar,
  toasts, modals, `fmtDate`/`statusBadge`/`escHtml` helpers) is in.

## Real-time: Laravel Reverb + Echo

The driver dashboard was originally Socket.IO-based; last round we made
`connectWebSocket()` a permanent no-op so every action fell back to REST.
This round wires up **actual push** via Laravel Reverb, Laravel's own
first-party WebSocket server (Pusher-protocol compatible), so nothing
external needs to be provisioned.

**Server side:**

- `config/broadcasting.php` — `reverb` is the default connection.
- `bootstrap/app.php` — `withBroadcasting(routes/channels.php, ['middleware' => ['auth:sanctum']])`
  registers `/broadcasting/auth`, guarded by the same Sanctum token the
  rest of the API uses.
- `routes/channels.php` — authorizes three channels:
  - `driver.{id}` — that driver only (assignment status, report-submitted,
    profile-updated, stop-updated)
  - `dispatcher.{id}` — that driver or any admin (driver-moved, new-report
    from a driver's own quick report)
  - `reports` — any authenticated user (new-resident-report,
    new-announcement, report-status-changed, truck-location,
    assignment-public-status)
- `App\Events\RealtimeEvent` — broadcasts on `PrivateChannel`s matching the
  above. `DriverController`, `ResidentController::createReport`, and
  `Admin\AnnouncementController::store` all fire it at the right moments.

**Client side (driver dashboard only, for now):**

- `resources/views/driver/dashboard.blade.php` loads `pusher-js` +
  `laravel-echo` from a CDN (no npm/Vite build needed for this
  vanilla-JS app) after `dashboard.js`, then
  `public/js/driver/echo-bridge.js`.
- `echo-bridge.js` is a **plain script sharing dashboard.js's global
  scope** — it reads/mutates `allIssues`, `todaysAssignments`, calls
  `toast()`/`loadToday()`/etc. directly, the same functions the old
  `socket.on(...)` handlers called. No rewriting of dashboard.js's
  internals was needed, just re-pointing the transport.
- It authenticates against `/broadcasting/auth` by sending the Sanctum
  token as a Bearer header (see the `auth.headers` block in
  `echo-bridge.js`) — there's no cookie/session in this app, so Echo's
  default cookie-based auth wouldn't have worked without this.

**Not yet wired:** the resident dashboard is still pure REST-polling (it
never used sockets originally). Adding an Echo bridge there for
`truck-location` and `new-announcement` would be a very similar copy of
`echo-bridge.js` — ask if you want that done too.

**Running it:** `php artisan reverb:start` in dev; in production run it as
a supervised process (systemd/Supervisor) or behind `reverb:start --host=0.0.0.0`
with a reverse proxy terminating TLS in front of it. Update the
`REVERB_*` env vars and `window.__reverbConfig` values in
`driver/dashboard.blade.php` to match wherever Reverb actually listens.

## Bugs fixed this round (found via actual tooling, not just review)

I got PHP 8.3 + Composer + SQLite installed in my environment this round
(wasn't available before), which let me actually lint and statically
analyze the code instead of just reading it. Two of these are the kind of
bug that would have completely broken the app on first request:

1. **`App\Http\Controllers\Controller` (the base class) never existed.**
   Every single controller in the app — all 14 of them — declares
   `class X extends Controller`, but the file itself was never created.
   This would have been a fatal `Class "App\Http\Controllers\Controller"
not found` error on the _first_ request to _any_ route. Found by
   cross-referencing every `App\...` class reference in the codebase
   against what actually exists on disk; added the missing file (it's
   supposed to be an empty abstract base class — that's normal, not a
   sign anything else is missing).
2. **`bootstrap/app.php` pointed `commands:` at `routes/console.php`,
   which didn't exist.** This file gets `require`'d during console
   bootstrap, so it would have broken **every artisan command** —
   `migrate`, `key:generate`, `serve`, all of it — not just some
   niche feature. Added the standard file (just registers the built-in
   `inspire` command, same as a stock Laravel install).
3. Verified (not found broken, but worth stating): I cross-checked every
   model's `$fillable`/`$casts` against its actual migration columns, every
   migration's foreign keys against table-creation order, every Blade
   `@section`/`@if`/`@foreach`/`@push` for balance, every `route()` call
   against defined route names (accounting for group name-prefixing), and
   every `view()`/`Route::view()` target against files on disk. All clean.
4. Lint pass: `php -l` on all 63 PHP files and `node --check` on all 18 JS
   files — zero syntax errors.

**What I still can't do here:** `repo.packagist.org` isn't in my network
allowlist, so `composer install` can't actually resolve/download Laravel
itself — I can lint and statically analyze, but I can't boot the real
framework, run migrations against a live DB, or hit an endpoint end to
end. The two bugs above are exactly the kind that only show up doing
that, so I'd treat "boot it for real and click around" as the next step,
not a redundant one.

## Bugs fixed (previous round)

1. **The zip wasn't actually a runnable Laravel install.** Missing:
   `artisan`, `public/index.php`, `bootstrap/providers.php`,
   `app/Providers/AppServiceProvider.php`, and every default `config/*.php`
   file (`app`, `auth`, `database`, `filesystems`, `cache`, `session`,
   `queue`, `logging`, `mail`). `composer install` on the old zip would
   have installed vendor packages and then had nowhere to boot from. All
   added now, plus `config/sanctum.php` and `config/reverb.php` which the
   token-auth and real-time pieces specifically depend on.
2. **`config/auth.php` had no `sanctum` guard.** Every `auth:sanctum`
   middleware check (i.e. every protected route in the app) would have
   401'd regardless of a valid token, because there was no guard for it
   to resolve against.
3. **Sanctum's `personal_access_tokens` table was never migrated.**
   `User::createToken()` in `AuthController::login` would have thrown a
   "table doesn't exist" error on literally the first login attempt.
   Added the migration directly rather than relying on a separate
   `install:api` step.
4. **`.env.example` set `SESSION_DRIVER=database` / `CACHE_STORE=database`
   with no `sessions`/`cache` table migrations** — every single web page
   (they all run through the `web` middleware group, which starts a
   session) would 500 on first load. Switched defaults to `file`/`file`/
   `sync`, which need zero extra tables.
5. **`DriverController::getLocationHistory` selected `route_assignments.zone_name`**,
   a column that doesn't exist — `zone_name` lives on `routes`. Added the
   missing join and fixed the select.
6. Missing `storage/` and `bootstrap/cache/` directory structure — Laravel
   writes logs, compiled views, and package-discovery cache there on
   every request/install; without them, `composer install` itself would
   fail at the `package:discover` post-install script.

## Known gaps

1. **CSS mismatch on the auth pages — needs your call.** `login.html`,
   `register.html`, `forgot-password.html`, and `verify-email.html` all
   link only `/css/auth.css` (gold theme). But `login.css`/`register.css`
   (green "glow" theme, completely different `:root` variables) were
   uploaded separately and **aren't linked from any page** — nothing
   currently loads them. More importantly: `verify-email.html` uses
   `.bg`, `.mobile-brand`, `.step`/`.step.active`, `.success-state`, and
   `.success-icon` classes that **don't exist in `auth.css` at all** —
   `.bg`/`.mobile-brand`/`.step` are only defined in `login.css`, and
   `.success-state`/`.success-icon` aren't in _any_ of the three files.
   As shipped, `verify-email.html` will render with its background image,
   mobile header, and success checkmark screen completely unstyled.
   `forgot-password.html` is fine either way — it carries its own inline
   `<style>` block for `.step`. I left the `<link>` tags exactly as given
   (pointing at `auth.css`) rather than guess; let me know whether
   `login.css`/`register.css` should replace `auth.css` on those two
   pages, and whether there's a `verify-email.css` (or additions to one
   of the others) to cover the missing classes.
2. **`auth.css`'s Google Fonts import looks broken**:
   `@import url('https://googleapis.com');` isn't a valid Fonts URL (no
   `/css2?family=...` path) — it'll just silently fail to load and fall
   back to system fonts. Probably meant something like
   `https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap`
   (which is what `verify-email.html` already links directly, separately).
3. **No PHP runtime in the environment this was built in** — nothing has
   been run through `composer install`, `php artisan migrate`, or a
   linter. Do that before deploying.
4. **Resident dashboard has no Echo bridge yet** — still REST-polling
   only (driver dashboard has the real one).
5. **`IPROG_API_TOKEN`** unset in dev — `SmsService` logs the message
   instead of sending it, so local dev works without a real SMS account.

## Node middleware parity (admin.js / auth.js / roleMiddleware.js)

These three confirm the shape of what's already built, no new code needed:

- `auth.js`'s `protect` (JWT verify + DB lookup + in-memory revocation
  `Set` that resets every hour and isn't shared across server processes)
  → Laravel's `auth:sanctum` middleware, which does the token lookup
  automatically and revokes instantly on `logout()`
  (`$request->user()->currentAccessToken()->delete()`) via the database —
  strictly more correct than the original's blacklist, since it survives
  restarts and works across multiple app servers.
- `adminOnly`/`driverOnly`/`residentOnly` / `roleMiddleware.js`'s factory
  → `App\Http\Middleware\EnsureRole`, applied as `role:admin`,
  `role:driver`, `role:resident` in `routes/api.php`.
