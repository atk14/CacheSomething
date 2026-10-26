# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

`atk14/cache-something` is a small PHP caching helper library for the ATK14 Framework. It provides a memoization layer on top of ATK14's `Cache`/`ObjectCacher` mechanism (from `atk14/table-record`), batching lookups of model instances (or arbitrary values) by id so that N objects can be fetched in one query instead of N queries. The entire implementation is three files in `src/`.

## Commands

Install dependencies:

```
composer install
```

Run the full test suite (requires a running PostgreSQL instance with a `test`/`test` user and `test` database — see `.github/workflows/tests.yml` for exact setup):

```
cd test && ../vendor/bin/run_unit_tests
```

Run a single test case by file or class name:

```
cd test && ../vendor/bin/run_unit_tests tc_cache_something
```

The runner (`atk14/tester`) scans the working directory for `tc_*.php` files; `test/initialize.php` bootstraps the DB schema and autoloads `test/models/` before tests run.

There is no separate lint/build step.

## Architecture

### Class hierarchy

- **`BaseCacheSomething`** (`src/base_cache_something.php`) — shared engine. Holds the cache array, the user-supplied loader `$function`, an optional `$model` class name, and `$options`. All the actual batching/memoization logic lives in `_get(&$cache, $fce, $ids, $options)`:
  - Normalizes a single id vs. an array of ids (`$one` flag), maps objects to ids via `mapIds()`.
  - Computes which ids are missing from `$cache` (or all of them if `force_read`), and — if a `$model` was given and `read_cached` is enabled — also opportunistically reads in anything already cached on the ATK14 `Cache` for that model (`Cache::CachedIds($this->model)`), so this cache doesn't duplicate work the framework's object cache already did.
  - Calls the loader `$fce($toRead, $options)` only for the missing ids, fills `default_value` for ids the loader didn't return, and merges results into `$cache`.
  - If constructed with `map_id`/`map_method`, results are returned mapped through that function (used by `CacheObjectProperty` below) instead of as a raw id => value array.
  - `only_cached` option (used by `getCached()`) skips the loader call entirely, returning only what's already cached.
- **`CacheSomething`** (`src/cache_something.php`) — the common case: id → value cache. `ClearCacheObject()` seeds the cache with `["" => null]` (a sentinel for null/empty keys, since PHP can't use `null` as an array key). `get($ids, $options)` is the public entry point. `CacheObjectProperty($resultObj, $model, $options)` is a factory for the frequent pattern "cache `$object->get{$resultObj}Id()` → resolve via `{$resultObj}::GetInstanceById()`".
- **`ParamCacheSomething`** (`src/param_cache_something.php`) — like `CacheSomething`, but the cache is partitioned by an extra `$params` value (e.g. cache per-filter-combination, not just per-id). `paramsToKey()` turns `$params` into an array key: objects with a `getId()` method become `"ClassName#id"`, other objects are `serialize()`d, scalars pass through. The loader signature is `function($params, $ids, $options)`.

### Integration with ATK14's `Cache`

When a `$model` is passed to the constructor, the instance registers itself with `Cache::GetObjectCacher($model)` so that if that cacher is (or becomes) an `ExtendedObjectCacher` (a hook for a not-yet-released `atk14/table-record` feature — currently always false with the installed version), it can notify this cache to clear itself when the underlying model cache clears. Don't be surprised the `instanceof` check never matches with the currently pinned `table-record` version; it's forward-compatible, not dead code to remove.

The real-world usage pattern (see `test/models/article.php`) is: a model class (e.g. `Article`) holds a static `CacheSomething` instance per relation, with a loader closure that does one batched SQL query for all pending ids and calls `Cache::Prepare(...)` for the result's own related ids, letting caches compose across models within one request.

### Test suite structure (`test/`)

- `test/initialize.php` loads the Composer autoloader, opens a PostgreSQL connection, and loads the `table-record` test SQL schema.
- `test/tc_base.php` wraps every test in a DB transaction (`begin`/`rollback` in `_setUp`/`_tearDown`) so tests don't leave data behind.
- `test/models/` defines minimal `TableRecord` subclasses (`Article`, `Author`, `Image`) used as fixtures; `Article::getImage()` is the canonical example of wiring `CacheSomething` into a model method.
- `test/fixtures/*.yml` are `atk14/mini-yaml`-based fixture files loaded via `@fixture` annotations on test classes (see `TcCacheSomething`); they support PHP interpolation (e.g. referencing another fixture's generated id).
- Tests assert cache behavior indirectly by comparing `$this->dbmole->getQueriesExecuted()` counts before/after repeated calls, proving a second lookup didn't hit the DB again.

### Versioning

`CHANGELOG.md` is maintained manually, listing released versions with their git commit hashes/subjects in `[x.y.z] YYYY-MM-DD` format. Update it (and bump the version) when making a user-facing change.
