<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Deploying on Wasmer Edge

Wasmer Edge can detect this Laravel project from its Composer manifest and `public/index.php`. Connect the repository in Wasmer and deploy it as a PHP/Laravel app; the project requires PHP 8.4.1 or newer.

Configure these production environment variables in Wasmer:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` — generate a key with `php artisan key:generate --show` and save it as a Wasmer secret
- `APP_URL` — the deployed Wasmer URL or your custom domain

Wasmer can provision a MySQL database and provide `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USERNAME`, and `DB_PASSWORD`. The application uses those values when selecting and configuring its MySQL connection. If you connect PostgreSQL instead, set `DB_CONNECTION=pgsql`; both connections accept Wasmer's `DB_NAME` variable. Run `php artisan migrate --force` once the database is available, using a Wasmer remote session or deployment task, before serving traffic. The database-backed session, cache, and queue drivers require the corresponding Laravel migrations to have run.

The deployment must build the Vite assets with `npm ci && npm run build` so the generated `public/build` files are included in the release.

The admin operations map is available at `/admin/map`; sign in at `/admin/login` with an existing user whose role is `admin`. It uses Leaflet with OpenStreetMap tiles and needs no map API key. Keep `MAP_TILE_URL` at its default unless you operate another tile provider.

To enable password-reset and pickup-driver SMS, set `IPROGSMS_API_TOKEN` in the local `.env` and as a Wasmer secret. Optionally set `IPROGSMS_SENDER_NAME`; the API base URL and timeout have defaults in `.env.example`. The token is sent only by the server to iProgSMS. Pickup reminders go to the assigned driver's phone because this schema does not associate residents with route zones.

The admin analytics AI endpoint is `POST /api/admin/analytics/insights`. It uses Gemini to summarize 30-day aggregate report and pickup counts, plus fleet and route totals; it does not send report descriptions or user details to the provider. Set `GEMINI_API_KEY` as a server-side secret locally and in Wasmer. `GEMINI_MODEL` and `GEMINI_TIMEOUT` have defaults in `.env.example`. Requests require an admin Sanctum token and are rate limited.

## CI/CD and Railway deployment

GitHub Actions runs the PHPUnit suite and builds the Vite assets on pushes and pull requests. A successful push to `main` is then deployed to Railway.

Configure these GitHub Actions repository secrets before enabling deployments:

- `RAILWAY_TOKEN`: a Railway project token
- `RAILWAY_PROJECT_ID`: the target Railway project ID
- `RAILWAY_SERVICE_ID`: the target application service ID

Set the application's production environment variables in Railway, including `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `SESSION_DRIVER=file`, `RAILPACK_SKIP_MIGRATIONS=true`, and a persistent production database. The Railway config runs `php artisan migrate --force` before starting the service, and checks the `/up` health endpoint. The locked PHP dependencies require PHP 8.4.1 or newer.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
