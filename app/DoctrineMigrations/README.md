# Database Migrations

This project now uses Doctrine Migrations as the canonical way to evolve the database schema.

Common commands:

```bash
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:migrations:migrate --dry-run --env=prod
php bin/console doctrine:migrations:migrate --env=prod
```

For environments that already imported the old SQL files manually, mark existing migrations as executed instead of rerunning them:

```bash
php bin/console doctrine:migrations:version 20260518000100 --add --env=prod
php bin/console doctrine:migrations:version 20260518000200 --add --env=prod
php bin/console doctrine:migrations:version 20260518000300 --add --env=prod
php bin/console doctrine:migrations:version 20260518000400 --add --env=prod
php bin/console doctrine:migrations:version 20260518000500 --add --env=prod
```

Use `--dry-run` first on production and back up the database before executing migrations.
