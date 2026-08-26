# Trove — Architecture Document

> Self-hosted image board / booru system built on Laravel (modular monolith), portable across SQLite and PostgreSQL.

## 1. Project Overview

Trove is a self-hosted media management and tagging platform inspired by booru-style image boards. It is designed for organizing, tagging, searching, and sharing image collections — primarily memes, screenshots, animals, reaction images, and other mixed visual content.

The system is built for personal use with the ability to share access with trusted users, but architecturally prepared to run as a public-facing community platform if desired.

### Design Philosophy

Trove deliberately targets a **permissive, shallow tagging model** rather than the exhaustive taxonomy of large public boorus. There is no expectation that a media item is tagged with every visible attribute, pose, or composition detail. The system must remain useful when an item has three tags, and must never require a minimum number of tags or a specific set of categories.

Consistency is achieved through **tooling rather than enforcement**: tag aliases collapse synonyms, tag implications fill in hierarchy automatically, and tag descriptions document intent. None of these are validation rules — they are conveniences that make loose tagging produce good search results.

The unit of content is a **single media item**. There are no galleries, albums, or multi-image posts. Every item has exactly one file and one public `hash_id`.

### Goals

- Clean, maintainable Laravel modular monolith codebase
- Tag-based organization with categories, aliases, and implications
- Flexible visibility and access control per media item
- Powerful search behind a swappable engine interface
- **Zero-dependency default deployment** — runs on SQLite with no external services
- Portable to PostgreSQL (and other Laravel-supported drivers) with a config change only
- Designed for open-source release (public GitHub repository)
- Internationalization (i18n) support from day one

### Non-Goals (MVP)

- Video support (see §13 Future Considerations)
- Collections / albums / galleries — one media item per post, permanently
- Comments system
- Auto-tagging / AI classification (architectural seam only, see §5.6)
- Multi-tenancy
- Federation or external API sync

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel (latest stable) |
| Database | SQLite (default) — PostgreSQL/MySQL supported via Laravel driver |
| Frontend | Inertia.js + Vue 3 + TypeScript + Vite |
| Storage | Laravel Storage facade — local disk first, S3/MinIO-compatible later |
| Thumbnails | Intervention Image (GD/Imagick driver, configurable) |
| Search | `MediaSearchEngine` interface — portable SQL engine ships by default |
| Cache | Configurable — `file` by default, Redis optional |
| Session | Configurable — `file` by default, Redis optional |
| Queue | Configurable — `database` by default, Redis optional |
| Reverse Proxy | Optional — Cloudflare for public deployments |
| Containerization | Docker |
| CI/CD | GitHub Actions |

### Database Portability

**No driver-specific SQL, column types, or extensions anywhere in the application.** This is a hard constraint, not a preference. Specifically:

- No PostgreSQL arrays, `tsvector`, `jsonb`, or GIN indexes
- No `ILIKE` — tags are normalized to lowercase, comparisons use `LOWER()`
- JSON columns use Laravel's `json` type and model casts, which map correctly on every driver
- Recursive CTEs are permitted (SQLite ≥ 3.8.3 and PostgreSQL both support them)
- All enum-like values are `VARCHAR` with an index; PHP enums are used at the domain level only

Driver-specific optimizations belong in optional `MediaSearchEngine` implementations, never in the core schema or models.

### Deployment Baseline

A default installation requires only PHP, the application, and a writable directory. `docker compose up` starts a single container. SQLite runs in WAL mode. Operators running a public instance with meaningful write concurrency should switch `DB_CONNECTION` to `pgsql` and `QUEUE_CONNECTION` to `redis`; no code changes are required.

---

## 3. Module Structure

The application is organized as a modular monolith. Each module encapsulates its own domain logic, models, routes, controllers, actions, and services.

```
app/
└── Modules/
    ├── User/          # Registration, profile, preferences, invitations
    ├── Auth/          # Authentication, authorization, roles, middleware
    ├── Media/         # Upload, storage, thumbnails, metadata, visibility, favorites
    ├── Tag/           # Tags, categories, aliases, implications, import/export
    └── Search/        # Search interface, engines, query parsing
```

### Controller / Action Pattern

Controllers are slim. All domain operations live in single-purpose Action classes (`AttachTagsToMedia`, `RebuildTagImplications`, `UploadMedia`). Both the Inertia endpoints and the public REST API call the same Actions. This keeps a future MCP server, CLI command, or queue job able to invoke the same logic without duplication.

### Module Responsibilities

**User** — Registration (open / invite / closed, runtime-configurable), profile management, user preferences (default safety filter, locale, UI settings), invitation system (generate, track, expire invite tokens).

**Auth** — Login/logout, two roles: `admin` and `user`. Admin has full access to all content and management features. Regular users can only manage their own uploads. Implemented via middleware and Laravel Gates; no RBAC package.

**Media** — The core module. File upload (single and bulk), storage abstraction, thumbnail generation pipeline, metadata extraction, visibility control, safety rating, anonymity flag, and user favorites. Favorites are a boolean pivot and do not warrant a separate module; they live here.

**Tag** — Tag CRUD, tag categories (name, color, sort order), tag aliases (transparent redirect to a canonical tag), tag implications (automatic hierarchy expansion), tag descriptions, tag merging, and taxonomy import/export.

**Search** — Defines a `MediaSearchEngine` interface with swappable implementations. Ships with `DatabaseSearchEngine` using portable SQL. Includes a `SearchQueryParser` that converts booru-style query strings into structured `SearchQuery` value objects.

---

## 4. Database Schema

Auto-increment `id` is used internally. Public-facing URLs use `hash_id` (random 10-character base62 string, unique indexed).

### `users`

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar (unique) | Display name, used in URLs |
| email | varchar (unique) | Nullable if email is optional in open registration |
| email_verified_at | timestamp | Nullable |
| password | varchar | |
| role | varchar | `admin`, `user` — indexed |
| status | varchar | `active`, `pending`, `banned` — indexed |
| default_safety_filter | varchar | `safe`, `sketchy`, `unsafe` |
| locale | varchar | Nullable — falls back to browser header, then site default |
| timestamps | | |

### `invitations`

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| token | varchar (unique) | Random token for invite URL |
| invited_by | bigint FK → users | |
| invited_email | varchar | Nullable — invite may not be email-bound |
| used_at | timestamp | Nullable — set when redeemed |
| used_by | bigint FK → users | Nullable |
| expires_at | timestamp | |
| timestamps | | |

### `site_settings`

Key-value store for runtime configuration.

| Column | Type | Notes |
|---|---|---|
| key | varchar PK | |
| value | text | JSON-encoded value |

**Known keys:**

- `registration_mode` — `open` / `invite`
- `registration_email_required` — boolean
- `registration_email_verification` — boolean
- `registration_admin_approval` — boolean
- `invite_who_can_invite` — `everyone` / `permitted` / `admin_only`
- `allow_anonymous_posting` — boolean, whether users may hide their attribution
- `default_visibility` — default for new uploads
- `default_safety_rating` — default for new uploads
- `duplicate_upload_policy` — `warn` / `reject`

### `media`

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| hash_id | varchar(10) (unique) | Public URL identifier |
| user_id | bigint FK → users | Uploader — **NOT NULL**, always a real account |
| is_anonymous | boolean | Default false — hides attribution in the UI only |
| title | varchar | Nullable |
| description | text | Nullable |
| source | varchar | Nullable — origin URL or credit |
| visibility | varchar | `public`, `authenticated`, `unlisted`, `private` — indexed |
| safety_rating | varchar | `safe`, `sketchy`, `unsafe` — indexed |
| original_filename | varchar | |
| mime_type | varchar | Indexed |
| filesize | bigint | Bytes |
| width | integer | Pixels |
| height | integer | Pixels |
| is_animated | boolean | Default false — animated GIF/WebP |
| frame_count | integer | Nullable — for animated formats |
| content_hash | varchar(64) | SHA-256 of file content — **indexed, not unique** |
| storage_path | varchar | Internal path, never exposed |
| thumbnails | json | `{"thumb": "...", "preview": "..."}` |
| dominant_color | varchar(7) | Hex color code, e.g. `#A3C4F3` |
| tag_count | integer | Denormalized, default 0 — for sorting and untagged discovery |
| timestamps | | |

**Indexes:**

- `UNIQUE (hash_id)`
- `INDEX (content_hash)` — duplicate detection, deliberately not a unique constraint
- `INDEX (visibility)`, `INDEX (safety_rating)`, `INDEX (user_id)`, `INDEX (created_at)`, `INDEX (mime_type)`
- `INDEX (visibility, safety_rating, created_at)` — the common browse path

**On `content_hash`:** a unique constraint is wrong for a multi-user system. A user would be blocked from uploading a file that already exists in someone else's private collection, which leaks information and produces a confusing error. Duplicates are detected at upload time and handled per `duplicate_upload_policy`: `warn` shows the existing item (if the user may see it) and lets them proceed, `reject` blocks the upload.

**Video columns are intentionally absent.** See §13.

### `tags`

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar (unique) | Normalized: lowercase, trimmed, spaces → underscores |
| category_id | bigint FK → tag_categories | Nullable — uncategorized tags are valid |
| description | text | Nullable — when and why to use this tag |
| usage_count | integer | Denormalized, default 0 |
| timestamps | | |

**Indexes:** `UNIQUE (name)`, `INDEX (category_id)`, `INDEX (usage_count)`

`usage_count` drives autocomplete ordering and surfaces orphaned or near-duplicate tags. It counts pivot rows with `source = 'human'` only, so implied tags do not inflate the number a user sees.

`description` is the mechanism that keeps a permissive system coherent. Instead of validating tag usage, Trove documents it: a tag can carry a short note explaining its scope, which is shown on hover in autocomplete and on the tag's detail page.

### `tag_categories`

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | varchar (unique) | e.g. `general`, `character`, `copyright`, `meta` |
| color | varchar(7) | Hex color for UI display |
| sort_order | integer | Display ordering |
| is_default | boolean | Exactly one row is true — new tags land here |
| timestamps | | |

The migration creates **only** the `general` category, flagged `is_default`, because the system cannot function without a fallback. Every other category is supplied by an **optional seeder** (`php artisan trove:seed-categories`) that an operator may run or ignore. The repository must not ship one user's taxonomy as though it were canonical.

The default category cannot be deleted. Deleting any other category sets its tags' `category_id` to the default.

### `tag_aliases`

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| alias_name | varchar (unique) | The alias string, normalized like a tag name |
| tag_id | bigint FK → tags | Points to the canonical tag |
| timestamps | | |

An alias name must not collide with an existing tag name. Aliases are resolved at tag-attach time and at query-parse time, so an alias never reaches the pivot table.

### `tag_implications`

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| tag_id | bigint FK → tags | The implying tag |
| implied_tag_id | bigint FK → tags | The tag that is added automatically |
| timestamps | | |

**Composite unique:** `(tag_id, implied_tag_id)`
**Indexes:** `INDEX (tag_id)`, `INDEX (implied_tag_id)`

### `media_tag` (pivot)

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| media_id | bigint FK → media | |
| tag_id | bigint FK → tags | |
| source | varchar | `human`, `implied`, `import`, `ai` — indexed |
| tagged_by | bigint FK → users | Nullable |
| created_at | timestamp | |

**Composite unique:** `(media_id, tag_id)`
**Indexes:** `INDEX (tag_id, media_id)` — the primary search path
**Indexes:** `INDEX (media_id, tag_id)` — tag listing for a single item

The `source` column carries three responsibilities at once: it makes implication removal correct (§5.3), it lets the UI dim automatically-derived tags, and it is the seam through which future AI-generated tags enter the system without a migration (§5.6).

### `favorites`

| Column | Type | Notes |
|---|---|---|
| user_id | bigint FK → users | |
| media_id | bigint FK → media | |
| created_at | timestamp | |

**Composite unique:** `(user_id, media_id)`

---

## 5. Tag System

The tag system is the largest and most important part of Trove. It carries the features that let shallow tagging still produce good search results.

### 5.1 Normalization

Tag names are normalized on creation and lookup: trimmed, lowercased, internal whitespace converted to underscores. Comparison always goes through `LOWER()` for driver portability.

Tag names are user-generated content and are **not translated**. A tag may be in any language; multilingual collections are handled with aliases (see below), not with a translation layer.

### 5.2 Aliases

An alias is a **synonym**: two names for the same concept. `kitty` → `cat`. Applying an alias applies the canonical tag; searching for an alias searches the canonical tag. The alias never exists as a separate row in `media_tag`.

Aliases are also the mechanism for multilingual tagging. If a collection needs a name in another language for a concept that already has an English canonical tag, that name becomes an alias rather than a second tag. This keeps the tag namespace single-language while allowing anyone to type either form.

### 5.3 Implications

An implication is a **hierarchy relationship**: applying A also applies B, but A and B are not the same thing. `calico` → `cat` → `animal`. `tears` → `crying`.

This is what makes permissive tagging viable. A user tags an image `calico` and gets `cat` and `animal` for free, so a later search for `cat` finds it without anyone having typed that tag.

**Implications are materialized.** When a tag is attached, the full transitive closure of its implications is resolved and each implied tag is written into `media_tag` with `source = 'implied'`.

The alternative — expanding the implication tree at query time — would require a recursive CTE on every search, since searching `animal` must match everything that transitively implies `animal`. Implications change rarely and searches run constantly, so the cost belongs at write time.

**Removal semantics.** When a human-applied tag is removed, its implied tags are removed too, but only where no other remaining tag still implies them and the tag is not independently present with `source = 'human'`. This is why `source` exists. Concretely:

1. Delete the `human` row for the removed tag.
2. Recompute the implied closure of the item's remaining `human` rows.
3. Delete `implied` rows not in the new closure; insert any that are missing.

Implementing removal as "recompute the item's implied set" rather than as a targeted delete keeps the logic correct regardless of overlapping implication paths.

**Cycle prevention.** Before inserting an implication, verify that `implied_tag_id` does not already reach `tag_id` transitively. A recursive CTE handles this on both supported drivers. Self-implication is rejected trivially.

**Depth limit.** The closure resolver enforces a configurable maximum depth (default 10) as a defensive measure against pathological chains, even though cycles are prevented at insert time.

**Rebuilding.** Changing or deleting an implication does not retroactively alter existing media. A `php artisan trove:rebuild-implications` command deletes all `source = 'implied'` rows and re-derives them from the `human` rows. This is safe precisely because implied rows are never authoritative. The command is offered in the admin UI after any implication change.

### 5.4 Merging

Merging tag A into tag B moves A's pivot rows to B (respecting the composite unique constraint), repoints A's aliases to B, creates an alias `A` → `B`, migrates A's implications, and deletes A. Usage counts are recalculated for both.

### 5.5 Taxonomy Import / Export

The Tag module exposes import and export of the full taxonomy as a single JSON document: categories, tags with descriptions and category assignments, aliases, and implications.

```json
{
  "version": 1,
  "categories": [
    { "name": "character", "color": "#0A0", "sort_order": 1 }
  ],
  "tags": [
    { "name": "cat", "category": "general", "description": "Domestic cat." }
  ],
  "aliases": [
    { "alias": "kitty", "tag": "cat" }
  ],
  "implications": [
    { "tag": "calico", "implies": "cat" }
  ]
}
```

Import is additive by default with a `--replace` option. Implications are validated for cycles during import; conflicting entries are reported rather than silently skipped.

This solves two problems at once: it is the migration path for anyone arriving from an existing booru, and it is the reason the repository does not need to ship an opinionated default taxonomy. Community taxonomies can be distributed as JSON files instead of baked into migrations.

### 5.6 AI Tagging Seam

Auto-tagging is explicitly out of scope for the MVP. The only accommodation made now is the `source` column on `media_tag`, which already admits an `ai` value.

When auto-tagging is eventually built, it writes pivot rows with `source = 'ai'` and the existing removal, display, and usage-count logic applies unchanged. No migration and no schema change will be required. Nothing else about auto-tagging should be designed or built until then.

---

## 6. Search Architecture

### Interface

```php
interface MediaSearchEngine
{
    public function search(SearchQuery $query): SearchResult;
    public function index(Media $media): void;
    public function remove(Media $media): void;
}
```

- `SearchQuery` — Value object produced by `SearchQueryParser`. Contains included tag IDs, excluded tag IDs, fulltext terms, metadata filters, visibility context, safety filter, sort field/direction, and pagination.
- `SearchResult` — Collection of media items/IDs plus total count.
- `index()` and `remove()` — No-ops in the database engine; used by external engines to sync their indexes.

### Query Syntax

Booru-style search string parsed by `SearchQueryParser`:

```
cat crying -politics rating:safe sort:newest
```

- `cat` — include tag (AND logic across all included tags)
- `-politics` — exclude tag
- `rating:safe` — filter by safety rating
- `user:alice` — filter by uploader
- `tags:0` — items with no tags (discovery of untagged uploads)
- `sort:newest` — `newest`, `oldest`, `random`, `filesize`, `tag_count`
- Bare non-tag terms are matched as fulltext against title and description

Aliases are resolved at parse time. Because implications are materialized, no query-time tree expansion is needed: searching `cat` matches items tagged `calico` because those items already carry a `cat` row.

### DatabaseSearchEngine (portable SQL)

**Tag inclusion** uses the pivot with a grouped count:

```sql
SELECT media_id
FROM media_tag
WHERE tag_id IN (:ids)
GROUP BY media_id
HAVING COUNT(DISTINCT tag_id) = :count
```

**Tag exclusion** uses `NOT EXISTS` against the pivot.

Both rely on the `(tag_id, media_id)` composite index and perform well into the hundreds of thousands of media items on either driver. There is no denormalized tag array and therefore no cache to keep in sync — the pivot is the only source of truth for tag assignment.

**Fulltext** in the default engine is a portable `LOWER(title) LIKE` / `LOWER(description) LIKE` match. This is deliberately modest: Trove is a tag-first system, and title/description search is a secondary path. Operators who need real fulltext implement an engine that uses their driver's capability.

### Optional Engines

Driver-specific and external search implementations live behind the same interface and are not part of the default install:

- `PostgresSearchEngine` — `tsvector` fulltext, optionally a denormalized tag array with a GIN index
- `SqliteFtsSearchEngine` — FTS5 virtual table for fulltext
- `MeilisearchSearchEngine` — external index, best relevance and typo tolerance

Swapping engines means implementing the interface, binding it in the service container, and running a one-time re-index from the pivot table. No controller, route, or parser changes.

---

## 7. Media Storage & Thumbnails

### Storage Abstraction

All file operations go through Laravel's `Storage` facade. Application code never uses direct filesystem paths.

- **Default:** `local` disk
- **Optional:** `s3` disk pointing to AWS S3, MinIO, or any S3-compatible provider

### Directory Structure

```
storage/
└── media/
    ├── originals/
    │   └── {hash_id}/original.{ext}
    └── thumbnails/
        └── {hash_id}/
            ├── thumb.webp      # ~150px, cropped square
            └── preview.webp    # ~850px wide, aspect preserved
```

### Thumbnail Generation

```php
interface ThumbnailGenerator
{
    public function generate(string $sourcePath, ThumbnailConfig $config): string;
    public function supports(string $mimeType): bool;
}
```

**Implementations (MVP):**

- `ImageThumbnailGenerator` — Intervention Image (GD or Imagick, configurable). Handles JPEG, PNG, WebP, static GIF.
- `AnimatedThumbnailGenerator` — Extracts the first frame from animated GIF/WebP for a static thumbnail.

All thumbnails are WebP.

### Supported MIME Types

MVP accepts: `image/jpeg`, `image/png`, `image/webp`, `image/gif`, `image/avif`. The list is configurable. Anything not on the list is rejected at validation.

### Upload Flow

1. User submits file(s) with metadata (tags, title, description, source, visibility, safety rating, anonymity).
2. Server validates MIME type and size limits.
3. Compute `content_hash` (SHA-256) and check for duplicates per `duplicate_upload_policy`.
4. Store the original via the Storage facade.
5. Extract metadata: dimensions, filesize, MIME type, dominant color, animation flag and frame count.
6. Generate `hash_id` (10-char random, verify uniqueness).
7. Create the `media` record.
8. Resolve tags: normalize, resolve aliases, create unknown tags in the default category, attach as `source = 'human'`, then resolve and attach the implication closure as `source = 'implied'`.
9. Update `usage_count` on affected tags and `tag_count` on the media item.
10. Dispatch thumbnail generation (sync or queued — configurable).

**Bulk upload** applies shared defaults across the batch, after which each item can be adjusted individually.

---

## 8. Visibility, Safety & Anonymity

### Visibility (per media item)

| Value | Behavior |
|---|---|
| `public` | Visible to everyone, including anonymous visitors |
| `authenticated` | Visible only to logged-in users |
| `unlisted` | Not shown in search/browse, accessible via direct `hash_id` link |
| `private` | Visible only to the uploader and admins |

### Safety Rating (per media item)

| Value | Behavior |
|---|---|
| `safe` | Appropriate for all audiences |
| `sketchy` | Borderline — hidden by default for most users |
| `unsafe` | Explicit — must be explicitly enabled in user preferences |

Each user has a `default_safety_filter`. Content at or below that level is shown. This is a **display filter, not access control** — the content exists and is reachable, it is simply not shown by default.

### Anonymity

There is **no anonymous upload**. Every media item has a real `user_id` and uploading requires an authenticated session. Anonymity is a per-item **display** flag chosen by the uploader.

When `is_anonymous` is true:

- The uploader's name is not shown on the media page, in search results, or via the API
- `user:` search filters do not match the item for non-admin users
- The uploader still sees it under their own uploads
- Admins still see the real uploader, for moderation

This preserves accountability and the "my uploads" view while giving users the option to post without attribution. Availability of the flag is controlled by the `allow_anonymous_posting` site setting.

**Constraint:** `is_anonymous` is only meaningful for `public`, `authenticated`, and `unlisted` items. Combining it with `private` is rejected at validation, since a private item is visible only to its uploader and admins — both of whom already know the author.

### Enforcement

Every query returning media passes through a single visibility scope that applies, in order:

1. `public` → visible to all
2. `authenticated` → visible if logged in
3. `unlisted` → excluded from search and browse, resolvable by `hash_id`
4. `private` → visible to uploader or admin only
5. Safety rating checked against the viewer's filter

Implemented as a dedicated query scope applied consistently across search, browse, single item, and favorites. It is the single chokepoint for access decisions and must not be bypassed.

---

## 9. Users & Authentication

### Roles

- **admin** — Full access: manage all media, tags, categories, implications, users, and site settings. Sees real uploaders behind anonymity flags.
- **user** — Upload media, manage own uploads, apply tags, manage favorites. Cannot see others' private content.

Enforced via Laravel Gates and middleware. No RBAC package.

### Tag Editing Permissions

Any active user may add or remove tags on any media item they can see. This is the booru convention and it is what makes collaborative tagging work. Destructive taxonomy operations — creating implications, merging tags, deleting tags, editing categories — are admin-only, because they affect the whole collection rather than a single item.

### Registration Modes

Controlled via `site_settings`, switchable at runtime:

**Open:** optionally require email, email verification, and/or admin approval (user created `pending`).
**Invite:** configurable who may invite (`everyone` / `permitted` / `admin_only`), tokens stored with expiry. `admin_only` is effectively closed registration.

### User Status

- `active` — normal access
- `pending` — awaiting admin approval; can log in, cannot interact
- `banned` — no access

---

## 10. Internationalization

- All user-facing strings go through Laravel's localization system. No hardcoded strings in Vue components or Blade templates.
- Vue receives translations via an Inertia shared prop; the same lang files serve both sides.
- Language files organized by module: `lang/{locale}/{user,auth,media,tag,search}.php`
- Locale resolution: user preference → `Accept-Language` header → site default
- Timestamps stored as UTC, displayed in the user's timezone
- **Tag and category names are not translated.** They are user-generated content; multilingual needs are served by aliases (§5.2).

---

## 11. API Design

Inertia endpoints serve the first-party frontend. A separate, narrower, versioned REST API serves external clients. Both call the same Action classes.

Authentication: session-based for the web frontend, Laravel Sanctum tokens for the API.

```
# Auth
POST   /api/v1/auth/login
POST   /api/v1/auth/logout

# Media
GET    /api/v1/media                        # Browse / search
POST   /api/v1/media                        # Upload
POST   /api/v1/media/bulk                   # Bulk upload
GET    /api/v1/media/{hash_id}
PATCH  /api/v1/media/{hash_id}
DELETE /api/v1/media/{hash_id}
POST   /api/v1/media/{hash_id}/tags         # Add tags
DELETE /api/v1/media/{hash_id}/tags         # Remove tags
POST   /api/v1/media/{hash_id}/favorite
DELETE /api/v1/media/{hash_id}/favorite

# Tags
GET    /api/v1/tags                         # List / autocomplete
POST   /api/v1/tags
PATCH  /api/v1/tags/{id}
DELETE /api/v1/tags/{id}
POST   /api/v1/tags/{id}/merge

# Tag categories, aliases, implications
GET    /api/v1/tag-categories
POST   /api/v1/tag-categories
PATCH  /api/v1/tag-categories/{id}
DELETE /api/v1/tag-categories/{id}
GET    /api/v1/tag-aliases
POST   /api/v1/tag-aliases
DELETE /api/v1/tag-aliases/{id}
GET    /api/v1/tag-implications
POST   /api/v1/tag-implications
DELETE /api/v1/tag-implications/{id}

# Taxonomy (admin)
GET    /api/v1/taxonomy/export
POST   /api/v1/taxonomy/import

# Favorites
GET    /api/v1/favorites

# Users, invitations, settings (admin)
GET    /api/v1/users
PATCH  /api/v1/users/{id}
POST   /api/v1/invitations
GET    /api/v1/settings
PATCH  /api/v1/settings
```

Rate limiting via Laravel's built-in limiter; Cloudflare as an optional additional layer for public deployments.

---

## 12. Design Principles

- **Driver portability is non-negotiable.** No database-specific SQL or types in core. Optimizations live behind interfaces.
- **VARCHAR over DB enum.** Always. Enums live in PHP.
- **One source of truth.** `media_tag` is authoritative for tag assignment; there is no denormalized tag array. `usage_count` and `tag_count` are caches and are rebuildable by command. Storage is authoritative for files; the thumbnails JSON is a convenience cache.
- **Derived data must be rebuildable.** Anything denormalized or materialized — implied tags, counts — has an artisan command that recomputes it from scratch.
- **Interface-driven extensibility.** Search engines, thumbnail generators, and storage backends are all swappable.
- **Guidance over enforcement.** No minimum tag count, no required categories, no tag validation rules. Consistency comes from aliases, implications, and descriptions.
- **Zero-dependency default.** A fresh install requires nothing beyond PHP and a writable directory.
- **Ship no opinionated taxonomy.** One default category, everything else optional or imported.
- **One media per post.** No albums, ever. This constraint is what keeps the data model and the URL space simple.
- **i18n from day one.** No hardcoded user-facing strings.
- **No premature abstraction.** Two roles, no RBAC. Simple favorites, no collections.

---

## 13. Future Considerations

Deliberately excluded from the MVP, recorded here so the design does not foreclose them.

**Video support.** The largest deferred item. It requires FFmpeg as a system dependency, a `VideoThumbnailGenerator`, and additional `media` columns (`duration`, `has_audio`, codec metadata). The `is_animated` / `frame_count` columns already present cover animated GIF/WebP and are not a substitute. Adding video is an additive migration plus a new `ThumbnailGenerator` implementation; nothing in the current design blocks it. The real cost is operational — FFmpeg, transcoding, storage, and streaming turn a simple deployment into a demanding one, which is why it stays out of a "runs on SQLite in one container" MVP.

**AI auto-tagging.** The `source` column already accommodates it (§5.6). A future implementation would add a `TagSuggester` interface and a review queue so suggestions are confirmed rather than applied blindly.

**Comments.** Straightforward additive module. Excluded because a personal instance does not need it and a public one needs moderation tooling alongside it.

**Pools / series.** Not albums — an ordered relationship between individually-posted media items, as boorus use for comic pages. This is the only form of grouping that would not violate the one-media-per-post rule, and would be a separate module with its own pivot.

**Post relationships.** Parent/child links between media items (original vs. edit, different resolutions of the same image).

**Federation / external sync.** Pulling from or mirroring other instances.

---

## 14. Open Questions

- **Thumbnail generation: sync vs queued default.** Sync is simpler for a single-user install; queued is better for bulk upload UX. Likely config with a sync default.
- **Upload limits.** Maximum file size and per-user storage quota. Quota may not be needed at all for MVP.
- **Untagged discovery UX.** `tags:0` exists as a filter, but whether the UI should actively surface untagged uploads is undecided.
- **Implication rebuild trigger.** Whether the admin UI should offer the rebuild command after every implication change, or run it automatically in the background.
- **Tag description scope.** Whether a plain text field is sufficient or a fuller tag wiki (revisions, authorship) is warranted later.
- **SQLite write concurrency threshold.** At what point the documentation should recommend switching to PostgreSQL, and whether that guidance can be measured rather than guessed.
