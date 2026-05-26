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
			$this->cache = [ null => null ];
		} else {
			if(!is_array($ids)) {
				$ids = [ $ids ];
			} else {
				$ids = $ids;
			}
			$ids = $this->mapIds($ids);
			$this->cache[$params] = array_diff_key($this->cache, $ids) + [ null => null ];
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
			$this->cache[$key] = [ null => null ];
		}
		return $this->cache[$key];

	}

	function paramsToKey($params) {
		if(is_object($params)) {
			return $params->getId();
		}
		return $params;
	}

	function getCache($params) {
		$out = $this->_getCache($params);
		unset($out[null]);
		return $out;
	}
}
