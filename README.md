# Trove

Self-hosted image board / booru for organizing, tagging and searching a personal
media collection. Laravel modular monolith, Inertia + Vue 3 + TypeScript, runs on
SQLite with no external services.

---

> ## ⚠️ Early development — v0.2.0
>
> Trove is **pre-1.0**. Core features work (accounts, upload, tagging,
> settings, mail), but there is no upgrade path yet and the schema can still
> change between releases without a migration. Search and invitations are not
> built.
>
> Back up `database/database.sqlite` (or your configured database) before
> upgrading, and read the [release notes](CHANGELOG.md) first.

---

## What it is

Trove targets a **permissive, shallow tagging model** rather than the exhaustive
taxonomy of large public boorus. An item with three tags is still useful.
Consistency comes from tooling instead of rules:

- **Aliases** collapse synonyms (`kitty` → `cat`), and double as the multilingual
  tagging mechanism.
- **Implications** fill in hierarchy automatically (`calico` → `cat` → `animal`),
  materialized at write time so searches stay a single indexed join.
- **Tag descriptions** document what a tag is for, instead of validating its use.

Other design commitments:

- **One media item per post.** No albums, galleries or multi-image posts, ever.
- **Zero-dependency default.** SQLite, file cache, database queue — nothing else
  to run. PostgreSQL and Redis are a config change, not a code change.
- **No driver-specific SQL anywhere in core.** Optimizations live behind a
  swappable `MediaSearchEngine` interface.
- **i18n from day one.** No hardcoded user-facing strings.

Read [`ARCHITECTURE.md`](ARCHITECTURE.md) for the full design: schema, tag
system, search, visibility model and API surface.

## Status

| Area | State |
|---|---|
| Docker environment | working |
| Inertia + Vue 3 + TypeScript scaffold | working |
| Users, auth, ranks, bans | working |
| Media upload, storage, thumbnails, visibility | working |
| Tags, aliases, implications | working |
| Settings (registration, mail, system) | working |
| Mail delivery, verification, password reset | working |
| Admin UI (users, tags, settings) | working |
| Public user profiles | working |
| Per-user timezone and date/time formats | working |
| Console user administration (`user:*`) | working |
| Search | not started |
| Invitations | not started |
| Sanctum / external API clients | not started |

## Requirements

Docker and Docker Compose. Nothing else — no PHP, Composer or Node on the host.

## Running it

```bash
cp .env.example .env
docker compose up -d
```

The container installs dependencies, creates the SQLite database, generates the
app key and runs migrations on first start. The app is then at
`http://localhost:8000` (change `APP_PORT` in `.env`), with the Vite dev server
on port `5173`.

Common commands:

```bash
docker compose exec -u www-data php php artisan test
```

```bash
docker compose exec node npm run types
```

See [`AGENTS.md`](AGENTS.md) for the full command reference and the conventions
this codebase follows.

## Releases

Each GitHub release publishes a multi-arch (`amd64`/`arm64`) PHP runtime image
to `ghcr.io/belovai/trove`, tagged with the release version, its `major.minor`
and `latest`. See [`CHANGELOG.md`](CHANGELOG.md) for what changed per version.
Since 0.1.1 the image bakes the full application and the built frontend, so it
is deployable on its own — no source checkout bind-mounted alongside it. There
is no standalone deployment compose file yet.

## Stack

Laravel 13 · PHP 8.4 · SQLite (PostgreSQL/MySQL supported) · Inertia.js ·
Vue 3 · TypeScript · Vite · Tailwind CSS · nginx · Docker

## License

MIT — see [`LICENSE`](LICENSE).
