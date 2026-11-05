# Contributing

## Development setup

```bash
git clone https://github.com/Zeusse-Neumbi/polyglot-db.git
cd polyglot-db
cp .env.docker.example .env.docker
bash docker/scripts/start.sh
```

The stack starts at <http://localhost>. See the README for service URLs and
ports.

For host-based PHP development, copy `.env.example` to `.env`, point it at the
running database containers, then run `composer install` and `php artisan serve`.

## Checks

```bash
composer install
php artisan test
npm ci
npm run build
```

Live database checks are opt-in:

```bash
RUN_DB_INTEGRATION_TESTS=1 php artisan test --filter=DatabaseConnectionTest
```

## Pull requests

- Keep changes focused and explain the operational effect.
- Update the README or `docs/` when setup, ports, or architecture changes.
- Do not commit `.env.docker`, `.env`, database dumps, generated reports, or
  container volumes.
- Do not claim benchmark results without a committed harness and raw output.
