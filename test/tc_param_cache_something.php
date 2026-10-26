<?php
class TcParamCacheSomething extends TcBase {

	function test_paramsToKey_distinguishes_objects_of_different_classes_with_same_id(){
		$cache = new ParamCacheSomething(function($params, $ids, $options){ return []; });

		$foo = new _TcParamCacheSomethingFoo(5);
		$bar = new _TcParamCacheSomethingBar(5);

		$key_foo = $cache->paramsToKey($foo);
		$key_bar = $cache->paramsToKey($bar);

		$this->assertEquals("_TcParamCacheSomethingFoo#5", $key_foo);
		$this->assertEquals("_TcParamCacheSomethingBar#5", $key_bar);
		$this->assertNotEquals($key_foo, $key_bar);
	}

	function test_get_does_not_mix_cache_of_objects_of_different_classes_with_same_id(){
		$calls = [];
		$cache = new ParamCacheSomething(function($params, $ids, $options) use (&$calls){
			$calls[] = $params;
			return array_fill_keys($ids, get_class($params)."#".$params->getId());
		});

		$foo = new _TcParamCacheSomethingFoo(5);
		$bar = new _TcParamCacheSomethingBar(5);

		$value_foo = $cache->get($foo, 1);
		$value_bar = $cache->get($bar, 1);

		$this->assertEquals("_TcParamCacheSomethingFoo#5", $value_foo);
		$this->assertEquals("_TcParamCacheSomethingBar#5", $value_bar);
		$this->assertEquals(2, count($calls)); // both loaders were actually called, no cache was reused by mistake

		// repeated calls are served from cache, loader is not called again
		$cache->get($foo, 1);
		$cache->get($bar, 1);
		$this->assertEquals(2, count($calls));
	}
}

class _TcParamCacheSomethingFoo {
	private $id;
	function __construct($id){ $this->id = $id; }
	function getId(){ return $this->id; }
}

class _TcParamCacheSomethingBar {
	private $id;
	function __construct($id){ $this->id = $id; }
	function getId(){ return $this->id; }
}
