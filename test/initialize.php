<?php
require(__DIR__ . "/../vendor/autoload.php");

setlocale(LC_NUMERIC,"C"); // we need to display float like 123.456

function &dbmole_connection($dbmole){
	static $connections = array();

	if($dbmole->getDatabaseType()=="postgresql"){
		if(!isset($connections["postgresql"])){
			$connections["postgresql"] = pg_connect("dbname=test user=test password=test host=127.0.0.1");
		}
		return $connections["postgresql"];
	}
}

// Creating testing structures
$GLOBALS["dbmole"] = PgMole::GetInstance();
$GLOBALS["dbmole"]->doQuery(file_get_contents(__DIR__."/../vendor/atk14/table-record/test/structures.postgresql.sql"));

define("PATH_ATK14_APPLICATION",__DIR__);
$GLOBALS["ATK14_GLOBAL"] = Atk14Global::GetInstance();

class_autoload(__DIR__."/models/");
