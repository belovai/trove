# Changelog

All notable changes to Trove are documented here. Dates are release dates,
not merge dates.

## [0.1.0] — Unreleased

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
