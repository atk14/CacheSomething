<?php
class ParamCacheSomething extends BaseCacheSomething {
	static function ClearCacheObject() {
		return [ ];
	}

	function clearCache($params=null, $ids = null) {
		$params = $this->paramsToKey($params);
		if($this->options['clear_condition'] && !$this->options['clear_condition']($params, $ids)) {
			return;
		}
		if($ids === null && $params == null) {
			$this->flushCache();
		} elseif($ids === null) {
			$this->cache = [ "" => null ];
		} else {
			if(!is_array($ids)) {
				$ids = [ $ids ];
			} else {
				$ids = $ids;
			}
			$ids = $this->mapIds($ids);
			$this->cache[$params] = array_diff_key($this->cache, $ids) + [ "" => null ];
		}
	}

	function get($params, $ids, $options = []) {
		$fce = function($ids, $options) use ($params) { 
			$fce = $this->function;
			return $fce($params, $ids, $options);
		};
		$cache = &$this->_getCache($params);
		return $this->_get($cache, $fce, $ids, $options);
	}

	function &_getCache($params) {
		$key = $this->paramsToKey($params);
		if(!key_exists($key, $this->cache)) {
			$this->cache[$key] = [ "" => null ];
		}
		return $this->cache[$key];

	}

	function paramsToKey($params) {
		if(is_object($params)) {
			if(method_exists($params,"getId")){
				return get_class($params)."#".$params->getId();
			}
			// serialize() is unsafe here: objects without getId() are often
			// request-scoped helpers (e.g. PriceFinder) carrying live, mutating
			// state (db handles, growing caches), so their serialized form keeps
			// changing between calls and defeats the cache. Key by instance
			// identity instead - stable for as long as the same instance is reused.
			return get_class($params)."#".spl_object_id($params);
		}
		return $params;
	}

	function getCache($params) {
		$out = $this->_getCache($params);
		unset($out[""]);
		return $out;
	}
}
