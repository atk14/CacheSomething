<?php
/**
 * $cache = new CacheSomething(function($ids, $options) {
 *    return array [ 'id' => ObjectSDanymId, 'id2' => ObjectSDanymId, ...... ]
 * })
 * $cache->get($objectId1);
 * >> Object1
 * Multiple objects, $objectId2 not exists
 * $cache->get([$objectId, $objectId2, ....]);
 * >> [ $objectId => Object1, $objectId2 => null, .... ]
 */

class CacheSomething extends BaseCacheSomething {
	static function ClearCacheObject() {
		return [ null => null ];
	}

	function clearCache($ids = null) {
		if($this->options['clear_condition'] && !$this->options['clear_condition']($ids)) {
			return;
		}
		if($ids === null) {
			$this->flushCache();
		} else {
			if(!is_array($ids)) {
				$ids = [ $ids ];
			} else {
				$ids = $ids;
			}
			$ids = $this->mapIds($ids);
			$this->cache = array_diff_key($this->cache, $ids) + [ null => null ];
		}
	}

	function get($ids, $options = []) {
		return $this->_get($this->cache, $this->function, $ids, $options);
	}
}
