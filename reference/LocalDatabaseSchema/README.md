# Local Database Schema Guide

`reference/LocalDatabaseSchema` remains the source of truth for the application data model, but it is no longer executed directly against the database at runtime.

## Current schema model

- `schema.json` is the manifest that lists tables and seeds.
- Each table entry points to a file in `tables/*.table.json` and carries a version number.
- Each table file declares `Columns`, `Indexes`, `UniqueIndexes`, and `ForeignKeys`.
- Columns use SQL-like types such as `INT`, `TEXT`, `DATE`, `DATETIME`, `VARCHAR(n)`, `CHAR(36)`, and `DECIMAL(p,s)`.
- Seed files declare a table, a mode such as `ensure_missing_rows` or `insert_all_if_table_empty`, optional match/patch metadata, and rows with optional lookup definitions.

## How this differs from Laravel migrations

- The legacy format describes the desired end-state of each table instead of a step-by-step change history.
- Laravel migrations are ordered PHP classes that apply discrete schema changes and are tracked in the `migrations` table.
- Laravel seeders are executable classes, while the JSON seed files are declarative data inputs.

## New workflow

1. Update the JSON files in this folder.
2. Run `php artisan schema:generate-migrations` to generate Laravel migrations and seeders.
3. Review the generated files in `database/migrations` and `database/seeders`.
4. Run `php artisan migrate` and `php artisan db:seed`.

## Mapping notes

- The generator maps the current JSON type set to Laravel Schema Builder columns.
- Foreign keys are emitted in a separate generated migration so table creation order stays predictable.
- If a future JSON type cannot be expressed cleanly with Laravel’s Schema Builder, the generator should surface that clearly and fall back to the smallest possible raw-SQL fragment.
