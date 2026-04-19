# PeopleCine Modern

This directory contains the new Laravel-based replacement for the legacy `wboard` PHP application.

The goal is to preserve the original community content while moving the platform onto a maintainable foundation with:

- Laravel 13 and PHP 8.3
- a normalized application schema
- import tooling for the legacy MySQL dump
- cleaner routing, templating, and future feature development

## Local Toolchain

The Laravel/PHP/Node toolchain was installed separately and can be loaded into the current PowerShell session with:

```powershell
. C:\CODEX\peoplecine\modernization\toolchain\use-laravel-toolchain.ps1
```

That script adds `php`, `composer`, `node`, `npm`, and `laravel` to `PATH`.

## Local Database

This app currently uses SQLite for local development. In this workspace, SQLite writes were more reliable from the user temp directory than from inside the project folder, so `.env` points to:

```text
C:/Users/ohm/AppData/Local/Temp/peoplecine-modern.sqlite
```

If you move this project to another machine, update `DB_DATABASE` in `.env` to a writable location or switch to MySQL.

## First Run

From `C:\CODEX\peoplecine\modern-app`:

```powershell
. C:\CODEX\peoplecine\modernization\toolchain\use-laravel-toolchain.ps1
php artisan migrate:fresh --force
php artisan peoplecine:import-legacy --fresh
php artisan serve
```

The importer defaults to the legacy dump at:

```text
C:\CODEX\peoplecine\all_mysql_backup.sql\all_mysql_backup.sql
```

You can also pass a custom dump path:

```powershell
php artisan peoplecine:import-legacy "C:\path\to\dump.sql" --fresh
```

## Imported Legacy Domains

The current import pipeline covers:

- members and profile data
- rooms and room metadata
- topics and replies
- attachments and bookmarks
- article categories, articles, and article blocks
- private messages

## Useful Commands

```powershell
php artisan route:list
php artisan about --only=environment
php artisan test
```

## Member Access During Migration

Imported legacy users now keep their old passwords during migration. The importer hashes the
legacy plaintext password into Laravel's password storage during import.

That means the intended sign-in flow is:

1. Open `/login`
2. Sign in with the old username and old password
3. If the account already has an email address, that email can be used for login too
4. If the account does not have an email yet, add one later from `/profile`

Users without email on file cannot sign in by email until they add one in their profile.

## Current Direction

This is a foundation build, not the final product yet. The next major steps are:

- replace the archive-oriented starter UI with the full new product design
- migrate authentication from insecure legacy behavior to modern Laravel auth flows
- add admin tooling and moderation workflows
- move local development and production onto MySQL
- introduce new community features on top of the imported archive
