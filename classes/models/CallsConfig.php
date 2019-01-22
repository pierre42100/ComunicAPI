<?php
/**
 * Calls configuration object
 * 
 * @author Pierre HUBERT
 */

class CallsConfig {

	//Private fields
	private $enabled = false;
	private $signal_server_name;
	private $signal_server_port;
	private $stun_server;
	private $turn_server;
	private $turn_username;
	private $turn_password;


	//Set and get enabled state
	public function set_enabled(bool $enabled){
		$this->enabled = $enabled;
	}

	public function get_enabled() : bool {
		return $this->enabled;
	}
	

	//Get Set Signal Server name
	public function set_signal_server_name(string $signal_server_name){
		$this->signal_server_name = $signal_server_name == "" ? null : $signal_server_name;
	}

	public function has_signal_server_name() : bool {
		return $this->signal_server_name != null;
	}

	public function get_signal_server_name() : string {
		return $this->signal_server_name != null ? $this->signal_server_name : "null";
	}


	//Set and get Signal Server Port
	public function set_signal_server_port(int $signal_server_port){
		$this->signal_server_port = $signal_server_port;
	}

	public function has_signal_server_port() : bool {
		return $this->signal_server_port > 0;
	}

	public function get_signal_server_port() : int {
		return $this->signal_server_port;
	}


	//Get and set stun server
	public function set_stun_server(string $stun_server){
		$this->stun_server = $stun_server == "" ? null : $stun_server;
	}

	public function has_stun_server() : bool {
		return $this->stun_server != null;
	}

	public function get_stun_server() : string {
		return $this->stun_server != null ? $this->stun_server : "null";
	}


	//Get and set turn server
	public function set_turn_server(string $turn_server){
		$this->turn_server = $turn_server == "" ? null : $turn_server;
	}

	public function has_turn_server() : bool {
		return $this->turn_server != null;
	}

	public function get_turn_server() : string {
		return $this->turn_server != null ? $this->turn_server : "null";
	}


	//Get and set turn username
	public function set_turn_username(string $turn_username){
		$this->turn_username = $turn_username == "" ? null : $turn_username;
	}

	public function has_turn_username() : bool {
		return $this->turn_username != null;
	}

	public function get_turn_username() : string {
		return $this->turn_username != null ? $this->turn_username : "null";
	}

	
	//Get and set turn password
	public function set_turn_password(string $turn_password){
		$this->turn_password = $turn_password == "" ? null : $turn_password;
	}

	public function has_turn_password() : bool {
		return $this->turn_password != null;
	}

	public function get_turn_password() : string {
		return $this->turn_password != null ? $this->turn_password : "null";
	}
}