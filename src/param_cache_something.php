<?php
class ParamCacheSomething extends BaseCacheSomething {

	protected $identityKeys; // SplObjectStorage: object (without getId()) -> its assigned cache key
	protected static $identityCounter = 0;

	static function ClearCacheObject() {
		return [ ];
	}

	function flushCache() {
		parent::flushCache();
		$this->identityKeys = new SplObjectStorage();
	}

	function clearCache($params=null, $ids = null) {
		$original_params = $params;
		$params = $this->paramsToKey($params);
		if($this->options['clear_condition'] && !$this->options['clear_condition']($params, $ids)) {
			return;
		}
		if($ids === null && $params == null) {
			$this->flushCache();
		} elseif($ids === null) {
			$this->cache[$params] = [ "" => null ];
			if(is_object($original_params) && $this->identityKeys){
				unset($this->identityKeys[$original_params]);
			}
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

	// $ids is required in practice; it's only declared optional here to keep the
	// override compatible with BaseCacheSomething::getCached($ids, $options = []).
	function getCached($params, $ids = null, $options = []) {
		return $this->get($params, $ids, $options + ['only_cached' => true]);
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
			// spl_object_id()/spl_object_hash() alone are unsafe here: PHP recycles
			// them as soon as an object is destroyed, so a later, unrelated object
			// could be assigned the very same id and silently inherit stale cached
			// data. SplObjectStorage both keys by true object identity and holds a
			// strong reference to $params, which prevents it from being destroyed
			// (and its id recycled) for as long as it's used as a cache key.
			if(!$this->identityKeys){
				$this->identityKeys = new SplObjectStorage();
			}
			if(!isset($this->identityKeys[$params])){
				$this->identityKeys[$params] = get_class($params)."#".(self::$identityCounter++);
			}
			return $this->identityKeys[$params];
		}
		return $params;
	}

	function getCache($params) {
		$out = $this->_getCache($params);
		unset($out[""]);
		return $out;
	}
}
