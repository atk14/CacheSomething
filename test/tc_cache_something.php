<?php
/**
 * @fixture authors
 * @fixture articles
 * @fixture article_authors
 */
class TcCacheSomething extends TcBase {

	function test(){
		Cache::Prepare("Article",[1,2,3]);

		$cnt_1 = $this->dbmole->getQueriesExecuted();

		$image_1 = $this->articles["article_1"]->getImage();
		$this->assertEquals(1,$image_1->getId());

		$cnt_2 = $this->dbmole->getQueriesExecuted();
		$this->assertTrue($cnt_2>$cnt_1);

		$image_2 = $this->articles["article_2"]->getImage();
		$this->assertEquals(2,$image_2->getId());

		$cnt_3 = $this->dbmole->getQueriesExecuted();
		$this->assertEquals($cnt_2,$cnt_3);

		$image_3 = $this->articles["article_3"]->getImage();
		$this->assertEquals(null,$image_3);
		
		$cnt_4 = $this->dbmole->getQueriesExecuted();
		$this->assertEquals($cnt_3,$cnt_4);
	}
}
