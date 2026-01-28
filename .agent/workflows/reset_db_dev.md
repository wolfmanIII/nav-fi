---
description: Reset Database (DEV) - Drop, Create, Migrate, Fixtures
---
WARNING: This will destroy all data in the configured database.
Use this only in a development environment.

1. Drop Database: `php bin/console doctrine:database:drop --force --if-exists`
2. Create Database: `php bin/console doctrine:database:create`
3. Run Migrations: `php bin/console doctrine:migrations:migrate --no-interaction`
4. Load Fixtures: `php bin/console doctrine:fixtures:load --no-interaction`
