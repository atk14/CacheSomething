CacheSomething
==============

[![Tests](https://github.com/atk14/CacheSomething/actions/workflows/tests.yml/badge.svg)](https://github.com/atk14/CacheSomething/actions/workflows/tests.yml)

CacheSomething is a caching helper for the [ATK14 Framework](https://www.atk14.net/). It batches lookups by id so that fetching data for many objects at once costs one query instead of N, and memoizes the result for the rest of the request.

It is commonly used to cache the result of "give me the thing related to this record" style methods — e.g. a product's main image, a card's technical specifications, a product's discount — where the same relation is looked up for many records of a list, one by one.

Statements:
- Avoid N+1 queries without rewriting every list view into a bulk query by hand.
- The caller doesn't need to know which ids are already cached — just ask for one id or an array of ids, as needed, as many times as needed.

Installation
------------

Requires PHP >= 7.1.

    composer require atk14/cache-something

Basic usage
-----------

Wrap whatever loads your data (a closure, a static method, a callable) in a `CacheSomething` instance. The loader receives only the ids that are **not** cached yet, and must return an `[id => value]` array for them:

```php
<?php
class Article extends TableRecord {

	static $ImageCache;

	function getImage(){
		if(!self::$ImageCache){
			self::$ImageCache = new CacheSomething(function($ids){
				$dbmole = Article::GetDbmole();
				$rows = $dbmole->selectRows("SELECT id, image_id FROM articles WHERE id IN :ids", [":ids" => $ids]);
				Cache::Prepare("Image", array_column($rows, "image_id"));
				$out = array_fill_keys($ids, null);
				foreach($rows as $row){
					$out[$row["id"]] = Cache::Get("Image", $row["image_id"]);
				}
				return $out;
			}, "Image");
		}
		return self::$ImageCache->get($this);
	}
}
```

```php
$article_1->getImage(); // hits the database once for all articles asked so far
$article_2->getImage(); // served from cache if article_2's image was already read
```

`get()` accepts a single id/object or an array of them:

```php
$cache->get($product);              // -> single value
$cache->get([$product_1, $product_2]); // -> [id => value, id2 => value2]
```

The loader can be any PHP callable, not just a closure:

```php
Discount::$ProductsDiscounts = new CacheSomething(['Discount', 'ReadDiscountForProducts'], 'Product');

class Discount extends ApplicationModel {
	static $ProductsDiscounts;

	static function ReadDiscountForProducts($ids, $options){
		// ... one query for all $ids ...
		return $discounts_by_product_id;
	}

	static function GetDiscountDataForProduct($product, $options = []){
		return self::$ProductsDiscounts->get($product, $options);
	}
}
```

Integration with ATK14's object cache
--------------------------------------

Passing a model class name as the second constructor argument (`"Image"`, `"Product"`, ...) does two things:

* Inside the loader, ids already prepared/cached in ATK14's own `Cache`/`ObjectCacher` for that model are opportunistically read in too (`Cache::CachedIds($model)`), so `CacheSomething` doesn't duplicate work the framework's object cache already did.
* Resolved ids coming back from your query can be fed into `Cache::Prepare(...)` for the *next* relation, letting several `CacheSomething` instances compose into one batch of queries across a request — see `Cache::Prepare("Image", ...)` in the example above.

`CacheObjectProperty()` is a shortcut for the frequent pattern "take `$object->get{Thing}Id()`, resolve it via `{Thing}::GetInstanceById()`":

```php
Product::$Card = CacheSomething::CacheObjectProperty("Card", "Product");
// equivalent to caching the result of $product->getCardId() resolved through Card::GetInstanceById()
// usage: Product::$Card->get($product); // or ->get($product_id)
```

Clearing the cache
-------------------

```php
$cache->clearCache();        // flush everything
$cache->clearCache($ids);    // forget just these ids (or these objects, mapped to ids)
$cache->flushCache();        // same as clearCache() with no arguments
```

A model typically clears its own cache when records it serves are created, updated or destroyed:

```php
function destroy($destroy_for_real = null){
	if(isset(self::$MainCreators)){
		self::$MainCreators->clearCache();
	}
	return parent::destroy();
}
```

Options
-------

The third constructor argument accepts:

* `force_read` — ignore the cache and re-read every requested id. Also usable per-call: `$cache->get($ids, ["force_read" => true])`.
* `read_cached` (default `true`) — opportunistically piggy-back on ATK14's `Cache::CachedIds($model)`, as described above. Set to `false` to disable it.
* `map_id` — a `function($ids)` used instead of the default id extraction (`TableRecord::ObjToId`), e.g. to resolve objects to some mapped value before indexing the cache.
* `map_method` — shortcut for `map_id`: resolves instances of `$model` by id and calls `$instance->$map_method()` on each. Used internally by `CacheObjectProperty()`.
* `clear_condition` — a `function($ids)` (or `function($params, $ids)` for `ParamCacheSomething`) consulted by `clearCache()`; returning `false` skips the clear.
* `default_value` (default `null`) — value used for ids the loader didn't return anything for.

`getCached($ids, $options)` is a shortcut for `get($ids, $options + ["only_cached" => true])` — it never calls the loader, returning only what's already in the cache. (`ParamCacheSomething` has the matching `getCached($params, $ids, $options)`.)

ParamCacheSomething
--------------------

`ParamCacheSomething` is the same idea, but partitioned by an extra `$params` value — useful when the same ids need caching per filter/context combination (e.g. price lists per currency, or pricing per pricelist/date), instead of one global cache per id.

```php
$cache = new ParamCacheSomething(function($params, $ids, $options){
	// $params is whatever you passed to get(), e.g. a currency code or a Pricelist object
	// return [id => value, ...] for the given $ids under $params
});

$cache->get($currency, $products); // cached separately per $currency
```

Objects passed as `$params` are turned into cache keys via `"ClassName#id"` when they expose `getId()` (so objects of different classes sharing the same id never collide). Objects without `getId()` are keyed by instance identity instead (an internal counter, not by content), so the same object instance always maps to the same key while two different instances never collide — even if one of them was previously destroyed.

`$params` can also be a plain array, to bundle several logical values into one call without wrapping them in an object first:

```php
$cache->get(["currency" => "EUR", "date" => $date], $products); // one bucket per currency+date combination
```

Each element is converted to its own key the same way as above (so an object nested inside the array still gets the `getId()`/identity handling), and the resulting array of keys is what actually identifies the cache bucket — two calls with array params of the same content hit the same bucket, different content means a different one.

`clearCache($params, $ids)` and `getCached($params, $ids, $options)` mirror `CacheSomething`'s, just with the extra `$params` in front:

```php
$cache->clearCache($currency);        // forget everything cached under this $params
$cache->clearCache($currency, $ids);  // forget just these ids under this $params
$cache->getCached($currency, $ids);   // read-only: never calls the loader
```

Testing
-------

The test suite requires a PostgreSQL database:

    sudo -u postgres psql -c "CREATE USER test WITH PASSWORD 'test';"
    sudo -u postgres psql -c "CREATE DATABASE test OWNER test;"

Then run:

    cd test && ../vendor/bin/run_unit_tests

To run a single test case:

    cd test && ../vendor/bin/run_unit_tests tc_cache_something

License
-------

MIT

[//]: # ( vim: set ts=2 et: )
