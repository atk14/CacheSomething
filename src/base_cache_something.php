<?php
class BaseCacheSomething {

	protected $function;
	protected $cache;
	protected $model;
	protected $options;

	function __construct($function, $model = False, $options = []) {
		$this->options = $options + [
			'force_read' => false,
			'read_cached' => true,
			'map_id' => null,
			'map_method' => null,
			'clear_condition' => null,
			'default_value' => null
		];
		$this->function = $function;
		$this->model = $model;
		$this->cache = static::ClearCacheObject();

		if($this->options['map_method']) {
			$this->options['map_id'] = function($ids) {
				$ids= Cache::Get($this->model, array_filter($ids, "is_numeric")) + $ids;
				//all is object now
				$map_method = $this->options['map_method'];
				return array_map(function($v) use($map_method)  {return $v?$v->$map_method():null; }, $ids);
			};
		}

		if($model) {
			$cacher = Cache::GetObjectCacher($model);
			if($cacher instanceof ExtendedObjectCacher) {
				$cacher->registerToClear($this);
			}
		}
	}

	static function ClearCacheObject(){
		return [];
	}

	function mapIds($ids) {
			if($this->options['map_id']) {
				$fce = $this->options['map_id'];
				$ids = $fce($ids);
			} else {
				$ids = Tablerecord::ObjToId($ids);
			}
			return $ids;
	}

	function flushCache() {
		$this->cache = self::ClearCacheObject();
	}

	function _get(&$cache, $fce, $ids, $options = []) {
		$one = !is_array($ids);
		if($one) {
			$ids = [ $ids ];
		}

		$options += $this->options;
		$ids = $this->mapIds($ids);
		$keys = array_flip(array_filter($ids));

		if($options['force_read']) {
				$toRead = $keys;
		} else {
			  $toRead = array_diff_key($keys, $cache);
		}

		if($toRead && (!key_exists('only_cached', $options) || !$options['only_cached'])) {
			if($this->model && $options['read_cached']) {
				$add = $this->mapIds(Cache::CachedIds($this->model));
				$toRead +=
					array_diff_key(
						array_flip(array_filter($add)),
						$cache
					);
			}
			$toRead = array_flip($toRead);
			$toRead = array_combine($toRead, $toRead);
			$out = $fce($toRead, $options);
			$out += array_fill_keys($toRead, $this->options['default_value']);
			$cache = $out + $cache;
		}

		if($this->options['map_id']) {
			foreach($ids as &$id) {
				if(is_null($id)){ continue; }
				$id = $cache[$id];
			}
			$out = $ids;
		} else {
			$out = array_intersect_key($cache, $keys);
		}

		if( $one ) {
			$out = $out?current($out):null;
		}

		return $out;
	}

	function getCached($ids, $options = []) {
		return $this->get($ids, $options + ['only_cached' => true]);
	}
}
