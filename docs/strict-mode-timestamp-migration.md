# Strict-mode audit for the legacy timestamp migration

## Scope and package ownership

The migration reported as
`igniter.system::2021_09_06_010000_add_timestamps_to_tables` is not part of
this application's tracked `database/migrations` directory. It belongs to the
`tastyigniter/core` Composer package, required by this project as `^4.3`.

This checkout has neither a `composer.lock` file nor a committed `vendor`
directory. Consequently, it does not establish an exact Core release or the
path and contents of the migration shipped by that release. Do not create a
Composer patch from the historical application copy alone: first capture the
installed package version and migration source as described below.

## Source audit

No source-controlled file in the current tree contains the migration name,
`date_added`, `date_updated`, or the zero date `0000-00-00 00:00:00`. Thus the
current-tree grep matches classify as follows:

| Classification | Current tracked matches |
| --- | ---: |
| Actual migration | 0 (provided by `tastyigniter/core`) |
| Shared schema helper | 0 |
| Seed or fixture | 0 |
| Test | 0 |
| SQL dump | 0 |
| Runtime code | 0 |
| Unrelated package code | 0 |

The historical pre-Composer migration is available in Git object history at
`app/system/database/migrations/2021_09_06_010000_add_timestamps_to_tables.php`.
Its complete `up()` method processes:

| Table | Legacy column(s) | Resulting column(s) |
| --- | --- | --- |
| `countries` | none | `created_at`, `updated_at` |
| `currencies` | `date_modified` | `updated_at`, plus `created_at` |
| `languages` | none | `created_at`, `updated_at` |
| `mail_layouts` | `date_added`, `date_updated` | `created_at`, `updated_at` |
| `mail_templates` | `date_added`, `date_updated` | `created_at`, `updated_at` |
| `themes` | none | `created_at`, `updated_at` |

Its `down()` method is empty. The legacy fields retain a zero-date default
because calling `timestamp(...)->change()` preserves unspecified attributes of
the existing MySQL column. Renaming that changed column then carries the
invalid default into `created_at` or `updated_at`. Under strict mode, MySQL
correctly rejects the generated `DEFAULT '0000-00-00 00:00:00'` clause.

## Reproducible package inspection

Run these commands in the failing installation without changing SQL mode:

```bash
composer show tastyigniter/core --locked
composer show tastyigniter/core --path
find vendor/tastyigniter/core -type f \
  -name '*2021_09_06_010000_add_timestamps_to_tables.php' -print
php -r '$p=json_decode(file_get_contents("vendor/composer/installed.json"), true); foreach (($p["packages"] ?? $p) as $v) if (($v["name"] ?? null) === "tastyigniter/core") echo ($v["version"] ?? "unknown"), PHP_EOL;'
```

Then record the exact version, path, checksum, and complete `up()` and `down()`
methods:

```bash
MIGRATION=$(find vendor/tastyigniter/core -type f \
  -name '*2021_09_06_010000_add_timestamps_to_tables.php' -print -quit)
sha256sum "$MIGRATION"
sed -n '1,240p' "$MIGRATION"
```

## Required package correction

The correction belongs in the established Core package file (or in a Composer
patch pinned to the established package version), not in Laravel internals and
not in an application migration that would run after the failing migration.
For every legacy timestamp conversion, explicitly remove the zero-date default
and make the column nullable before or while renaming it. The resulting schema
must be equivalent to:

```sql
ALTER TABLE `ti_mail_layouts`
CHANGE `date_updated` `updated_at` TIMESTAMP NULL DEFAULT NULL;
```

Apply the same rule through the migration's shared conversion logic rather
than special-casing `mail_layouts`. The implementation must check the table
and both old and new column names so reruns tolerate all partial states:

1. Skip an absent table.
2. Convert and rename when the legacy name exists and the new name does not.
3. Normalize an existing new timestamp column to nullable/default-null.
4. Do nothing when neither column exists.
5. Repeat independently for every table and column so earlier successful
   conversions do not prevent later ones.

Do not disable strict mode, change global MySQL configuration, or replace
unrelated zero-date values. After patching the exact package release, retain
the patch file and Composer patch configuration in source control and commit
the generated `composer.lock` so the correction is reproducible.

## Runtime verification after the package is established

```bash
composer install
php artisan igniter:up
php artisan migrate:status
php artisan test
```

Verify the converted definitions against the application's configured table
prefix:

```sql
SELECT TABLE_NAME, COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, DATA_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
    'ti_countries', 'ti_currencies', 'ti_languages',
    'ti_mail_layouts', 'ti_mail_templates', 'ti_themes'
  )
  AND COLUMN_NAME IN ('created_at', 'updated_at');
```

Expected legacy conversions are nullable timestamps with a `NULL` default;
no converted definition may contain a zero-date default.
