# Trove

Self-hosted image board / booru for organizing, tagging and searching a personal
media collection. Laravel modular monolith, Inertia + Vue 3 + TypeScript, runs on
SQLite with no external services.

---

> ## ⚠️ Very early development — not usable yet
>
> This is a **pre-alpha work in progress**. There is no release, no upgrade path,
> and no stability guarantee of any kind.
>
> - Most of what the architecture document describes **is not built yet** —
>   no media upload, no tags, no search, no users.
> - The database schema **will change without migrations**. Expect to delete
>   `database/database.sqlite` and start over.
> - Anything here may be rewritten or removed without notice.
>
> **Do not run this on a public server, and do not put media you care about
> into it.** It is published this early only so the design can be read and
> discussed in the open.

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
| Media upload, storage, thumbnails | not started |
| Tags, aliases, implications | not started |
| Search | not started |
| Users, auth, invitations | not started |

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

## Stack

Laravel 13 · PHP 8.4 · SQLite (PostgreSQL/MySQL supported) · Inertia.js ·
Vue 3 · TypeScript · Vite · Tailwind CSS · nginx · Docker

## License

MIT — see [`LICENSE`](LICENSE).
