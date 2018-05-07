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
	private $name;
	private $token;
	private $url;

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


	//Get and set client URL
	public function set_url(string $url){
		$this->url = $url == "" ? null : $url;
	}

	public function has_url() : bool {
		return $this->url != null;
	}

	public function get_url() : string {
		return $this->url != null ? $this->url : "null";
	}

}