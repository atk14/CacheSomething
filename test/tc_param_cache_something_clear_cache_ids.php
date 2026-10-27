<?php
/**
 * Regression test: ParamCacheSomething::clearCache($params, $ids) used to compute
 * array_diff_key($this->cache, $ids) - diffing the whole top-level, params-keyed cache
 * against $ids (a plain list, so array_diff_key compared against positional indexes 0,1,2...
 * instead of the actual id values) and store the (almost unchanged) result back into
 * $this->cache[$params]. This both failed to remove the given ids and corrupted that params
 * bucket into a nested copy of the entire cache. Fixed by diffing $this->cache[$params] against
 * array_flip($ids).
 */
class TcParamCacheSomethingClearCacheIds extends TcBase {

	function test_clearCache_with_ids_forgets_only_the_given_ids(){
		$calls = [];
		$cache = new ParamCacheSomething(function($params, $ids, $options) use (&$calls){
			$calls[] = [$params, $ids];
			return array_fill_keys($ids, "v-$params");
		});

		$cache->get("a", [1, 2]);
		$this->assertEquals(1, count($calls));

		$cache->clearCache("a", [1]);

		// id 2 under "a" must still be cached
		$cache->get("a", 2);
		$this->assertEquals(1, count($calls));

		// id 1 under "a" was cleared, must be re-read
		$cache->get("a", 1);
		$this->assertEquals(2, count($calls));
	}

	function test_clearCache_with_ids_does_not_affect_other_params_buckets(){
		$calls = [];
		$cache = new ParamCacheSomething(function($params, $ids, $options) use (&$calls){
			$calls[] = [$params, $ids];
			return array_fill_keys($ids, "v-$params");
		});

		$cache->get("a", [1, 2]);
		$cache->get("b", [1, 2]);
		$this->assertEquals(2, count($calls));

		$cache->clearCache("a", [1]);

		$cache->get("b", 1);
		$this->assertEquals(2, count($calls));
	}

	function test_clearCache_with_ids_on_a_never_populated_params_does_not_crash(){
		$cache = new ParamCacheSomething(function($params, $ids, $options){
			return array_fill_keys($ids, "v-$params");
		});

		$cache->clearCache("never-used-before", 1);
		$this->assertTrue(true); // reaching here means it didn't throw/warn
	}
}
