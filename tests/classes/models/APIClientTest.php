<?php

//Include class
require_once(__DIR__."/../../../classes/models/APIClient.php");

use PHPUnit\Framework\TestCase;

class APIClientTest extends TestCase {

	public function testConfirmHasTokenAfterSet(){
		$client = new APIClient();
		$client->set_token("token");
		$this->assertEquals(TRUE, $client->has_token());
	}
}