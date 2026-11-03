<?php
/**
 * Regression test: same array_flip(null) warning as
 * TcCacheSomethingClearCacheWithANullId, but for ParamCacheSomething::clearCache($params,$ids).
 */
class TcParamCacheSomethingClearCacheIdsWithANullId extends TcBase {

	function test_clearCache_with_a_null_id_does_not_warn_and_still_clears_the_other_ids(){
		$calls = [];
		$cache = new ParamCacheSomething(function($params, $ids, $options) use (&$calls){
			$calls[] = [$params, $ids];
			return array_fill_keys($ids, "v-$params");
		});

		$cache->get("a", [1, 2]);
		$this->assertEquals(1, count($calls));

		$warnings = [];
		set_error_handler(function($errno, $errstr) use (&$warnings){
			$warnings[] = $errstr;
			return true;
		}, E_WARNING);
		$cache->clearCache("a", [1, null]);
		restore_error_handler();

		$this->assertEquals([], $warnings);

		// id 2 under "a" must still be cached
		$cache->get("a", 2);
		$this->assertEquals(1, count($calls));

		// id 1 under "a" was cleared, must be re-read
		$cache->get("a", 1);
		$this->assertEquals(2, count($calls));
	}
}
