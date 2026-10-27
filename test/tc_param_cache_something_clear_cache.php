<?php
/**
 * Regression test: ParamCacheSomething::clearCache($params) (without $ids) used to reset the
 * whole top-level $this->cache array to [ "" => null ] instead of just the bucket belonging to
 * the given $params, silently wiping out the cache of every other $params value as collateral
 * damage. Fixed by targeting $this->cache[$params] instead of $this->cache.
 */
class TcParamCacheSomethingClearCache extends TcBase {

	function test_clearCache_with_params_only_does_not_affect_other_params_buckets(){
		$calls = [];
		$cache = new ParamCacheSomething(function($params, $ids, $options) use (&$calls){
			$calls[] = $params;
			return array_fill_keys($ids, "v-$params");
		});

		$cache->get("a", [1, 2]);
		$cache->get("b", [1, 2]);
		$this->assertEquals(2, count($calls));

		$cache->clearCache("a");

		// "b" was never cleared, so it must still be served from cache
		$cache->get("b", 1);
		$this->assertEquals(2, count($calls));
	}

	function test_clearCache_with_params_only_does_clear_that_params_bucket(){
		$calls = [];
		$cache = new ParamCacheSomething(function($params, $ids, $options) use (&$calls){
			$calls[] = $params;
			return array_fill_keys($ids, "v-$params");
		});

		$cache->get("a", [1, 2]);
		$cache->clearCache("a");

		// "a" was cleared, so reading it again must hit the loader
		$cache->get("a", 1);
		$this->assertEquals(["a", "a"], $calls);
	}
}
