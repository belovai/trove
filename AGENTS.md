# Trove — Agent Guide

Self-hosted image board / booru. Laravel modular monolith, Inertia + Vue 3 +
TypeScript frontend, portable across SQLite and PostgreSQL.

The full design is in `ARCHITECTURE.md`. Read it before changing the schema,
the tag system, the search layer, or the visibility rules.

---

## 1. Everything runs in Docker

**Never run `php`, `composer`, `artisan`, `npm`, `node`, `vite`, or a test
suite on the host.** The host may have a different PHP version, no extensions,
or no toolchain at all. The containers are the only supported environment.

Start the stack:

```bash
docker compose up -d
```

| Task | Command |
|---|---|
| Artisan | `docker compose exec -u www-data php php artisan <command>` |
| Composer | `docker compose exec -u www-data php composer <command>` |
| Tests | `docker compose exec -u www-data php php artisan test` |
| Pint | `docker compose exec -u www-data php ./vendor/bin/pint` |
| Tinker | `docker compose exec -u www-data php php artisan tinker` |
| npm | `docker compose exec node npm <command>` |
| Type check | `docker compose exec node npm run types` |
| Frontend build | `docker compose exec node npm run build` |
| Logs | `docker compose logs -f php` |

Run PHP commands as `-u www-data`. Running them as root leaves root-owned files
in `storage/`, `bootstrap/cache/` and `vendor/`, which breaks php-fpm afterwards.

### Git hooks

The repository ships a `pre-commit` hook in `.githooks/`. `core.hooksPath` is
local git configuration and is not cloned, so enable it once per clone:

```bash
git config core.hooksPath .githooks
```

The hook regenerates the IDE helper model docblocks and runs Pint inside the
container, then re-stages the tracked files it touched.

### Services

- **php** — php-fpm 8.4, plus a `queue:work` and a `schedule:work` process under
  supervisor. Migrations run on container start (`TROVE_AUTO_MIGRATE=false` opts out).
- **nginx** — serves `public/`, port `${APP_PORT:-8000}`.
- **node** — Vite dev server on port `${VITE_PORT:-5173}`, started by `npm run dev`.

There is no database or cache container. SQLite, file cache and the database
queue driver are the defaults, and a default install must stay that way.
Anyone who wants PostgreSQL or Redis runs it themselves and points `.env` at it.

### After changing Docker files

Rebuild rather than restart: `docker compose build php && docker compose up -d php`.
`vite.config.ts` changes need `docker compose restart node`.

`imagick` and `redis` extensions are enabled in the image (needed for
Intervention Image's Imagick driver and animated WebP/GIF frame counting).
The queue worker command is `TROVE_QUEUE_COMMAND`, overridable per environment:
the image default is `queue:work` (keeps the framework booted, so it does not
see code changes without a restart); `compose.override.yaml` swaps in
`queue:listen` for local development, which reboots per job.

---

## 2. Code layout

Modules live under `modules/{ModuleName}/` — `User`, `Auth`, `Media`, `Tag`,
`Setting`, `Search`. Each module owns its models, migrations, routes,
controllers, actions and tests, and registers them from its own
`{Module}ModuleServiceProvider`. Nothing module-specific belongs in
`AppServiceProvider`.

A module that needs runtime-configurable behaviour declares it in its own
`Config/settings.php`, the same way `Config/privileges.php` declares
abilities: `modules/{Module}/Config/settings.php` returns a map of dot-path
keys to `SettingDefinition`s (type, default, encrypted flag, validation
rules). `{Module}ModuleServiceProvider` loads both files at boot. Read a
setting with `Settings::get('key')` — never `config()`, which only ever sees
the `.env` default. A key declared by two modules is a boot-time error, not a
last-one-wins silent override.

The `Modules\` PSR-4 namespace maps to `modules/` alongside `App\`. Module
tests live under `modules/{Module}/Tests/{Unit,Feature}/` and run as their own
`Modules` testsuite in `phpunit.xml`, distinct from `tests/`.

Controllers stay slim: validation, authorization, calling an Action, shaping the
response. All domain logic lives in single-purpose Action classes so the Inertia
endpoints, the REST API, artisan commands and queued jobs can call the same code.

PHP: `declare(strict_types=1)`, `final` classes, constructor property promotion,
typed properties, PHP enums for enum-like values. Eloquent models declare
`$fillable` and `$hidden` through the `#[Fillable]` and `#[Hidden]` attributes,
not protected properties. Format with Pint before finishing.

Frontend: Vue 3 `<script setup lang="ts">` single-file components under
`resources/js/pages/`, resolved by name through Inertia. `@/` is aliased to
`resources/js/`. `npm run types` must pass.

Front-end design rules — tokens, composition, buttons, overlays — live in
[`docs/design.md`](docs/design.md). Follow it for every UI change.

---

## 3. Hard constraints

These are load-bearing. Breaking one is a bug even if the tests pass.

- **Driver portability.** No driver-specific SQL, column types or extensions in
  core code: no `jsonb`, no PostgreSQL arrays, no `tsvector`, no GIN indexes, no
  `ILIKE`. Compare lowercased strings with `LOWER()`. Enum-like columns are
  `VARCHAR` with an index; the enum lives in PHP. Driver-specific optimizations
  belong behind the `MediaSearchEngine` interface, never in the schema.
- **One media item per post.** No albums, galleries or multi-file posts, ever.
- **`media_tag` is the single source of truth** for tag assignment. There is no
  denormalized tag array. `usage_count` and `tag_count` are caches.
- **Derived data must be rebuildable.** Anything materialized — implied tags,
  counts — needs an artisan command that recomputes it from scratch.
- **Visibility goes through one query scope.** Every query that returns media
  applies it. No ad-hoc `where('visibility', ...)` anywhere else.
- **No hardcoded user-facing strings.** Everything goes through Laravel
  localization, in module-scoped files under `modules/{Module}/Lang/{locale}/`.
  Vue receives translations as a flat, namespaced Inertia shared prop
  (`auth::login.title`). Tag and category names are user content and are never
  translated.
- **Ship no opinionated taxonomy.** Migrations create exactly one tag category
  (`general`, the default). Everything else is an optional seeder or a JSON import.
- **No tag name is normalized, parsed or compared outside `TagName`.** Trimming,
  lowercasing, whitespace-to-underscore, and the reserved-character/word checks
  live in exactly one value object. Nothing else touches a raw tag string.
- **No recursive query outside `ImplicationClosureResolver`.** Expanding an
  implication closure, walking ancestors, or checking reachability for cycle
  prevention all go through that one service — never a hand-written recursive
  CTE elsewhere.

---

## 4. Public repository

This repository is public. Never commit secrets, real user data, personal media,
or a `.env` file. `.env.example` carries safe placeholder values only.
