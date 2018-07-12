<?php

//Include class
require_once(__DIR__."/../../../classes/models/BaseUniqueObject.php");

use PHPUnit\Framework\TestCase;

class BaseUniqueObjectTest extends TestCase {

	public function testValidObjectWithoutId(){
		$obj = new BaseUniqueObject();
		$obj->set_id(10);
		$this->assertEquals(TRUE, $obj->isValid());
	}

	public function testInvalidObjectWithoutId(){
		$obj = new BaseUniqueObject();
		$this->assertEquals(FALSE, $obj->isValid());
	}
}