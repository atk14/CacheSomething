<?php
/**
 * Regression test: CacheSomething::clearCache($ids) used to compute
 * array_diff_key($this->cache, $ids) - $ids is a plain list (e.g. [0 => 5]), so
 * array_diff_key compared the cache's real-id keys against positional indexes 0,1,2...
 * instead of the actual id values, meaning the requested ids were never actually
 * removed from the cache. Fixed by diffing against array_flip($ids).
 */
class TcCacheSomethingClearCache extends TcBase {

	function test_clearCache_with_an_array_of_ids_forgets_only_those_ids(){
		$calls = [];
		$cache = new CacheSomething(function($ids) use (&$calls){
			$calls[] = $ids;
			return array_fill_keys($ids, "v");
		});

		$cache->get([5, 7]);
		$this->assertEquals(1, count($calls));

		$cache->clearCache([5]);

		$cache->get(7); // still cached
		$this->assertEquals(1, count($calls));

		$cache->get(5); // was cleared, must be re-read
		$this->assertEquals(2, count($calls));
	}

	function test_clearCache_with_a_single_id_forgets_only_that_id(){
		$calls = [];
		$cache = new CacheSomething(function($ids) use (&$calls){
			$calls[] = $ids;
			return array_fill_keys($ids, "v");
		});

		$cache->get([5, 7]);
		$this->assertEquals(1, count($calls));

		$cache->clearCache(5);

		$cache->get(7); // still cached
		$this->assertEquals(1, count($calls));

		$cache->get(5); // was cleared, must be re-read
		$this->assertEquals(2, count($calls));
	}
}
