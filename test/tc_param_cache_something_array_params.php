<?php
/**
 * ParamCacheSomething::paramsToKey() accepts a plain PHP array as $params too - useful to
 * bundle several logical parameters (e.g. currency + date) into one call without having to
 * wrap them in an object first. Each element is converted to its own key first (so nested
 * objects still go through the identity-safe/getId()-based handling), then the resulting
 * array of plain keys is serialized into the final cache key - never the raw elements
 * themselves, so this doesn't reintroduce the "serialize() of a live, stateful object"
 * problem paramsToKey() avoids for plain object params.
 */
class TcParamCacheSomethingArrayParams extends TcBase {

	function test_array_params_with_the_same_content_are_a_cache_hit(){
		$calls = [];
		$cache = new ParamCacheSomething(function($params, $ids, $options) use (&$calls){
			$calls[] = $ids;
			return array_fill_keys($ids, "v");
		});

		$cache->get(["currency" => "EUR", "date" => "2026-01-01"], 1);
		$cache->get(["currency" => "EUR", "date" => "2026-01-01"], 1);

		$this->assertEquals(1, count($calls));
	}

	function test_array_params_with_different_content_are_a_cache_miss(){
		$calls = [];
		$cache = new ParamCacheSomething(function($params, $ids, $options) use (&$calls){
			$calls[] = $ids;
			return array_fill_keys($ids, "v");
		});

		$cache->get(["currency" => "EUR", "date" => "2026-01-01"], 1);
		$cache->get(["currency" => "USD", "date" => "2026-01-01"], 1);

		$this->assertEquals(2, count($calls));
	}

	function test_array_params_containing_an_object_with_getId_key_by_that_id(){
		$calls = [];
		$cache = new ParamCacheSomething(function($params, $ids, $options) use (&$calls){
			$calls[] = $ids;
			return array_fill_keys($ids, "v");
		});

		$cache->get(["customer" => new _TcParamCacheSomethingArrayParamsWithId(5)], 1);
		// a different instance, but the same id -> still a cache hit
		$cache->get(["customer" => new _TcParamCacheSomethingArrayParamsWithId(5)], 1);

		$this->assertEquals(1, count($calls));
	}

	function test_array_params_containing_an_object_without_getId_key_by_instance_identity(){
		$calls = [];
		$cache = new ParamCacheSomething(function($params, $ids, $options) use (&$calls){
			$calls[] = $ids;
			return array_fill_keys($ids, "v");
		});

		$helper = new _TcParamCacheSomethingArrayParamsNoId("x");
		$cache->get(["ctx" => $helper], 1);
		$cache->get(["ctx" => $helper], 1); // same instance -> cache hit

		$this->assertEquals(1, count($calls));
	}
}

class _TcParamCacheSomethingArrayParamsWithId {
	private $id;
	function __construct($id){ $this->id = $id; }
	function getId(){ return $this->id; }
}

class _TcParamCacheSomethingArrayParamsNoId {
	private $label;
	function __construct($label){ $this->label = $label; }
	function getLabel(){ return $this->label; }
}
