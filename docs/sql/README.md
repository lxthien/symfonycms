# Legacy SQL Scripts

These SQL files are kept for reference and emergency/manual recovery only.

New schema changes should be added under:

```text
migrations/
```

Use Doctrine Migrations for releases:

```bash
php bin/console doctrine:migrations:status --env=prod
php bin/console doctrine:migrations:migrate --dry-run --env=prod
php bin/console doctrine:migrations:migrate --env=prod
```

Do not add new feature SQL files here unless there is a temporary production hotfix that cannot be expressed as a migration yet.
