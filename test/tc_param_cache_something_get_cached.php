<?php
/**
 * Regression test: ParamCacheSomething inherited BaseCacheSomething::getCached($ids, $options)
 * unchanged, but ParamCacheSomething::get() takes ($params, $ids, $options) - one argument more.
 * Calling getCached() on a ParamCacheSomething used to throw a fatal TypeError
 * ("Unsupported operand types: int + array") because $options ended up holding what was
 * meant to be $ids. Fixed by overriding getCached() with the correct ($params, $ids, $options)
 * signature.
 */
class TcParamCacheSomethingGetCached extends TcBase {

	function test_getCached_does_not_call_the_loader_when_nothing_is_cached_yet(){
		$calls = 0;
		$cache = new ParamCacheSomething(function($params, $ids, $options) use (&$calls){
			$calls++;
			return array_fill_keys($ids, "v-$params");
		});

		$this->assertNull($cache->getCached("a", 1));
		$this->assertEquals(0, $calls);
	}

	function test_getCached_returns_a_previously_cached_value_without_calling_the_loader_again(){
		$calls = 0;
		$cache = new ParamCacheSomething(function($params, $ids, $options) use (&$calls){
			$calls++;
			return array_fill_keys($ids, "v-$params");
		});

		$cache->get("a", [1, 2]);
		$this->assertEquals(1, $calls);

		$this->assertEquals("v-a", $cache->getCached("a", 1));
		$this->assertEquals(1, $calls); // still 1, getCached() must not trigger the loader
	}

	function test_getCached_does_not_leak_values_cached_under_a_different_params(){
		$calls = 0;
		$cache = new ParamCacheSomething(function($params, $ids, $options) use (&$calls){
			$calls++;
			return array_fill_keys($ids, "v-$params");
		});

		$cache->get("a", [1, 2]);

		$this->assertNull($cache->getCached("b", 1));
		$this->assertEquals(1, $calls); // only the "a" load, "b" was never read
	}
}
