# Changelog

All notable changes to Trove are documented here. Dates are release dates,
not merge dates.

## [0.2.0] — 2026-09-02

Public user profiles, per-user date/time preferences, and a console escape
hatch for user administration. No schema migration is provided for the two new
`users` columns beyond the migrations themselves — run `php artisan migrate`
after upgrading.

### Added

- Public profile page at `/u/{username}`: avatar placeholder, display name,
  rank, registration date, upload count and a paginated grid of that user's
  uploads. `users.show_uploads` hides the grid from other people; anonymous
  uploads are never attributed. Moderators bypass both and are told so.
  Uploader names on the media page and in the admin user list link here.
- Per-user timezone, date format and time format preferences, with
  instance-wide defaults in system settings. All timestamps in the UI are
  rendered through them (`resources/js/support/datetime.ts`).
- Download-original link on the media detail page.
- Console user administration, which runs no policy check on purpose:
  `user:create`, `user:ban`, `user:unban`, `user:rank`, `user:password`
  (generates and prints 16 characters when no password is given). This is the
  way back in when every web-facing administrator is locked out.
- Admin-generated passwords from the user list (`POST /settings/users/{user}/password`):
  shown once, rotate `remember_token`, and mail a notice — never the password —
  when the account has a deliverable address.
- Base tag categories are seeded (`TagCategorySeeder`) instead of only
  `general`.
- PHPStan (larastan) at level 8 over the whole codebase, wired into CI.

### Changed

- `UserPolicy::update()` now compares ranks strictly (`outranks()` rather than
  `outranksOrEquals()`). An administrator can no longer edit, ban, rank or
  reset another administrator, or themselves, from the web UI — admin-on-admin
  is a takeover, not an edit. The user list hides the edit control where the
  policy would refuse anyway.

### Known limitations

Unchanged from 0.1.0: no search, no invitations, no follower feed, no
`laravel/sanctum`, and no upgrade-path guarantee between 0.x releases.

## [0.1.3] — 2026-09-01

### Changed

- CI caches Composer dependencies and Docker layers.

## [0.1.2] — 2026-09-01

### Added

- Trusted proxies are configurable from `.env` (`TRUSTED_PROXIES`), so Trove
  behind a reverse proxy sees the real client IP and scheme.

### Fixed

- `package-lock.json` version kept in sync with `package.json`.

## [0.1.1] — 2026-08-30

### Fixed

- The released container image now bakes the full application and the built
  frontend, so it is deployable on its own instead of expecting the source
  tree to be bind-mounted alongside it.

## [0.1.0] — 2026-08-30

First tagged release. Core self-hosting loop works end to end; search and
invitations are not built yet.

### Added

- Docker stack (php-fpm 8.4 + nginx + node), SQLite by default, Postgres/MySQL
  supported without code changes.
- Accounts: five-rank privilege model, bans, i18n (en/hu), admin user
  management (create/edit/ban, rank changes).
- Media: upload, storage, thumbnails, per-item visibility and safety rating,
  duplicate detection, soft delete with scheduled pruning.
- Tags: aliases, implications (materialized hierarchy), categories, merge/
  rename/delete, co-occurrence-based related tags, taxonomy import/export.
- Settings: runtime-configurable registration policy, mail transport and site
  name, all editable from an admin settings UI without redeploying.
- Mail: pluggable transport (log/SMTP), email verification, password reset,
  account notice emails (pending registration, approval, ban).
- A shared UI design system (tokens, form/overlay primitives) used across all
  settings and media screens.
- Multi-arch (`amd64`/`arm64`) container image published to GHCR on release.

### Known limitations

- No search — the architecture's `MediaSearchEngine` interface exists but has
  no implementation yet.
- No invitations, no tag following/follower feed (both depend on search).
- No upgrade-path guarantee: the schema can still change between 0.x
  releases without a migration.
- `laravel/sanctum` is not installed; there is no external API client support.
