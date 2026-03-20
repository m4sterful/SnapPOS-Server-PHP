# Local Database Schema Guide

If all you need is startup creation of the database, tables, and missing columns, the minimum setup is:

1. Keep `schema.json`.
2. Define each table and its columns in `tables/*.table.json`.
3. Reference those files from `schema.json`.
4. Give each table a `version` number in `schema.json`.
5. Leave `seeds` as `[]` if you do not want default data.
6. Add columns to `tables/*.table.json`, increment that table's `version`, and restart the API.

That is enough for the initializer to:
- create the database if it does not exist
- create any missing tables
- add missing columns
- add missing indexes

Files:
- `schema.json`: the active manifest used at startup
- `schema.template.json`: minimal starter manifest
- `table.template.json`: minimal starter table definition
- `seed.template.json`: optional starter seed definition
- `tables/*.table.json`: one file per managed table
- `seeds/*.seed.json`: optional seed files

## Smallest Working Example

`schema.json` references the table files and stores the version number for each table.

`schema.json`

```json
{
  "schemaFormatVersion": 1,
  "tables": [
    {
      "tableName": "sys",
      "file": "tables/sys.table.json",
      "version": 1
    },
    {
      "tableName": "example_table",
      "file": "tables/example_table.table.json",
      "version": 1
    }
  ],
  "seeds": []
}
```

`tables/example_table.table.json`

```json
{
  "tableName": "example_table",
  "columns": [
    {
      "name": "example_text",
      "type": "VARCHAR(128)",
      "nullable": true
    }
  ],
  "indexes": [],
  "uniqueIndexes": [],
  "foreignKeys": []
}
```

## Notes

- `id INT AUTO_INCREMENT PRIMARY KEY` is automatic. Do not add it to JSON.
- Use `foreignKeys` to declare DB-level relationships. A foreign key can target the automatic `id` primary key or another unique key such as `guid`.
- For internal table-to-table relations, `id` is usually the better FK target. Use `guid` when you need a stable external identifier and make that column unique in the parent table.
- `sys` is required because the initializer stores table versions there.
- Increment a table's `version` in `schema.json` when you change that table file and want startup to apply missing columns or indexes.
- `versionKeyName` is optional. If omitted, the table name or seed name is used for version tracking in `sys`.
- Seed files are optional.

## When To Use Seeds

Only use `seeds/*.seed.json` when you want default rows inserted or patched at startup.

Supported seed modes:
- `ensure_missing_rows`
- `patch_existing_when_empty`
- `insert_all_if_table_empty`

## Safety Rules

- Missing tables are created automatically.
- Missing columns and indexes are added automatically when the DB version is behind the manifest version.
- Changed or unexpected schema still counts as drift and fails startup.
- Drift and initialization failures are logged to `C:\ProgramData\DOTPOS\dotpos-schema.log`.
