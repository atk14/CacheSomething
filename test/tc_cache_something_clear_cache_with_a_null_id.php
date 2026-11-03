<?php
/**
 * Regression test: CacheSomething::clearCache($ids) computes array_flip($ids) after
 * mapIds($ids) - if one of the given ids maps to null (e.g. an unsaved TableRecord object
 * with no id yet, or simply null passed through by the caller), array_flip() can't use null
 * as an array value and emits "array_flip(): Can only flip string and integer values, entry
 * skipped". Fixed by filtering out null entries before flipping, same as _get() already does.
 */
class TcCacheSomethingClearCacheWithANullId extends TcBase {

	function test_clearCache_with_a_null_id_does_not_warn_and_still_clears_the_other_ids(){
		$calls = [];
		$cache = new CacheSomething(function($ids) use (&$calls){
			$calls[] = $ids;
			return array_fill_keys($ids, "v");
		});

		$cache->get([5, 7]);
		$this->assertEquals(1, count($calls));

		$warnings = [];
		set_error_handler(function($errno, $errstr) use (&$warnings){
			$warnings[] = $errstr;
			return true;
		}, E_WARNING);
		$cache->clearCache([5, null]);
		restore_error_handler();

		$this->assertEquals([], $warnings);

		$cache->get(7); // still cached
		$this->assertEquals(1, count($calls));

		$cache->get(5); // was cleared, must be re-read
		$this->assertEquals(2, count($calls));
	}
}
