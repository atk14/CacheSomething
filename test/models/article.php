<?php
class Article extends TableRecord{

	static $ImageCache;
	function getImage(){
		if(!self::$ImageCache){
			self::$ImageCache = new CacheSomething(
				function($ids) {
					$ids += Cache::CachedIds("Article");
					$dbmole = Article::GetDbmole();
					$rows = $dbmole->selectRows(
						"
							SELECT
								id, image_id
							FROM
								articles WHERE id IN :ids
						",
						[":ids" => $ids]
					);
					Cache::Prepare("Image", array_column($rows, "image_id"));
					$out = array_fill_keys($ids, []);
					foreach($rows as $row){
						$id = $row["id"];
						$out[$id] = Cache::Get("Image",$row["image_id"]);
					}
					return $out;
				},
				"Image"
			);
		}
		return self::$ImageCache->get($this);
	}
}
