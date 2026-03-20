# Basic Testing Regimen

## 1. Static and automated validation

1. Install PHP dependencies with `composer install`.
2. Run the focused feature coverage for routing and setup behavior with `php artisan test --filter=SetupRoutingTest`.
3. Run the full automated suite with `php artisan test`.

## 2. First-run setup verification

1. Start the application locally with `php artisan serve`.
2. Open `/setup` while the application is uninstalled.
3. Submit the setup form with a fresh database target.
   - For SQLite, provide a new file path under `database/` and confirm the file is created automatically.
   - For MySQL or MariaDB, provide credentials for a server that allows the target database to exist before migration, then verify the migrations complete without errors.
4. Confirm the response reports `Installation completed successfully.` and returns an API base URL.
5. Verify the migrations table and generated schema tables exist in the configured database.

## 3. API smoke tests after installation

1. Request `GET /api` and confirm the API reports the installation is ready.
2. Request `GET /api/system` and confirm the response body is exactly `pong`.
3. Request `GET /api/system/schema-validation` and confirm the response includes `schema_base_path` and `manifest_path` values that point to the LocalDatabaseSchema source data.
4. Request at least one other stub endpoint such as `GET /api/admin` and confirm it still returns the JSON stub payload.

## 4. Regression checks before merge

1. Re-run `php artisan test` after any setup-flow or routing changes.
2. If the setup flow writes a persistent `.env` or database file during manual verification, clean up or document the resulting state before shipping.
3. Record the exact commands and outcomes from the manual setup and ping smoke tests in the change summary or PR notes.
