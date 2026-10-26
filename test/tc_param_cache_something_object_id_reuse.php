<?php
/**
 * Regression test for a bug introduced in commit 97b912e: ParamCacheSomething::paramsToKey()
 * used to key objects without getId() by spl_object_id(), but PHP recycles an object's id
 * as soon as it is destroyed - so two completely unrelated instances could end up sharing
 * the same cache key and one would silently receive the other's stale cached data.
 *
 * Fixed by keying such objects through a SplObjectStorage-backed identity map with its own
 * never-reused counter, which also keeps the object alive for as long as it's used as a key.
 */
class TcParamCacheSomethingObjectIdReuse extends TcBase {

	function test_paramsToKey_does_not_collide_for_unrelated_instances_once_the_first_one_is_destroyed(){
		$cache = new ParamCacheSomething(function($params, $ids, $options){ return []; });

		$helper_1 = new _TcParamCacheSomethingHelper("first");
		$key_1 = $cache->paramsToKey($helper_1);

		unset($helper_1); // would free $helper_1's spl_object_id for reuse, if nothing else held it

		$helper_2 = new _TcParamCacheSomethingHelper("second"); // a brand new, unrelated instance
		$key_2 = $cache->paramsToKey($helper_2);

		$this->assertNotEquals($key_1, $key_2);
	}

	function test_paramsToKey_returns_the_same_key_for_the_same_instance(){
		$cache = new ParamCacheSomething(function($params, $ids, $options){ return []; });

		$helper = new _TcParamCacheSomethingHelper("first");

		$this->assertEquals($cache->paramsToKey($helper), $cache->paramsToKey($helper));
	}

	function test_get_does_not_return_stale_value_of_a_destroyed_unrelated_instance(){
		$cache = new ParamCacheSomething(function($params, $ids, $options){
			return array_fill_keys($ids, $params->getLabel());
		});

		$helper_1 = new _TcParamCacheSomethingHelper("first");
		$value_1 = $cache->get($helper_1, 1);
		$this->assertEquals("first", $value_1);

		unset($helper_1); // would free $helper_1's spl_object_id for reuse, if nothing else held it

		$helper_2 = new _TcParamCacheSomethingHelper("second"); // a brand new, unrelated instance
		$value_2 = $cache->get($helper_2, 1);

		$this->assertEquals("second", $value_2);
	}
}

class _TcParamCacheSomethingHelper {
	private $label;
	function __construct($label){ $this->label = $label; }
	function getLabel(){ return $this->label; }
}
