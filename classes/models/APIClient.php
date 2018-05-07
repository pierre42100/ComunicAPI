<?php
/**
 * API Client
 * 
 * @author Pierre HUBERT
 */

//This class requires the BaseUniqueObject class to be loaded
require_once __DIR__."/BaseUniqueObject.php";

class APIClient extends BaseUniqueObject {

	//Private fields
	private $time_insert;
	private $name;
	private $token;
	private $client_domain;

	//Set and get creation time
	public function set_time_insert(int $time_insert){
		$this->time_insert = $time_insert;
	}

	public function get_time_insert() : int {
		return $this->time_insert;
	}

	//Get and set client name
	public function set_name(string $name){
		$this->name = $name == "" ? null : $name;
	}

	public function has_name() : bool {
		return $this->name != null;
	}

	public function get_name() : string {
		return $this->name != null ? $this->name : "null";
	}


	//Get and set client token
	public function set_token(string $token){
		$this->token = $token == "" ? null : $token;
	}

	public function has_token() : bool {
		return $this->token != null;
	}

	public function get_token() : string {
		return $this->token != null ? $this->token : "null";
	}


	//Get and set client domain
	public function set_client_domain(string $client_domain){
		$this->client_domain = $client_domain == "" ? null : $client_domain;
	}

	public function has_client_domain() : bool {
		return $this->client_domain != null;
	}

	public function get_client_domain() : string {
		return $this->client_domain != null ? $this->client_domain : "null";
	}

}