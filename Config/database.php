<?php

class DATABASE_CONFIG {

	public $default = array(
		'datasource' => 'Database/Postgres',
		'persistent' => false,
		'host' => 'localhost',
		'login' => 'postgres',
		'password' => 'palla1',
		'database' => 'postgres',
		'prefix' => '',
		//'encoding' => 'utf8','password' => 'I7mkw06Ss4','database' => 'haamble',:5432
	);

    	public $Postgres = array(
		'datasource' => 'Database/Postgres',
		'persistent' => false,
		'host' => 'localhost',
		'login' => 'postgres',
		'password' => 'palla1',
		'database' => 'test1',
		'prefix' => '',
		//'encoding' => 'utf8','password' => 'I7mkw06Ss4','database' => 'haamble',:5432
	);

	public $test = array(
		'datasource' => 'Database/Mysql',
		'persistent' => false,
		'host' => 'localhost',
		'login' => 'user',
		'password' => 'password',
		'database' => 'test_database_name',
		'prefix' => '',
		//'encoding' => 'utf8',
	);
}
