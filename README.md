# SnapPOS Server PHP

## Laravel-native setup workflow

1. On first run, browse to `/setup`.
2. Save the Laravel environment values into `.env`.
3. The setup controller runs `php artisan key:generate`, `php artisan config:clear`, and `php artisan migrate`.
4. If you choose to seed defaults, it also runs `php artisan db:seed --class=Database\\Seeders\\GeneratedLocalSchemaSeeder`.

## JSON schema workflow

1. Treat `reference/LocalDatabaseSchema/*.json` as the source of truth.
2. Generate executable Laravel artifacts with `php artisan schema:generate-migrations`.
3. Review the generated files in `database/migrations` and `database/seeders`.
4. Apply them with `php artisan migrate` and `php artisan db:seed`.

The application no longer mutates the database schema directly from JSON at request time.
