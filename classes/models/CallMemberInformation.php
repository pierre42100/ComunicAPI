<?php
/**
 * Information about two members call
 * 
 * @author Pierre HUBERT
 */

class CallMemberInformation extends BaseUniqueObjectFromUser {
	
	/**
	 * Possible user responses
	 */
	public const USER_ACCEPTED = 1;
	public const USER_REJECTED = 0;
	public const USER_UNKNOWN  = -1;
	public const USER_HANG_UP = 2;


	
	//Private fields
	private $call_id;
	private $user_call_id;
	private $accepted;



	//Call ID
	public function set_call_id(int $call_id){
		$this->call_id = $call_id;
	}

	public function has_call_id() : bool {
		return $this->call_id > -1;
	}

	public function get_call_id() : int {
		return $this->call_id;
	}


	//User Call ID
	public function set_user_call_id(string $user_call_id){
		$this->user_call_id = $user_call_id == "" ? null : $user_call_id;
	}

	public function has_user_call_id() : bool {
		return $this->user_call_id != null;
	}

	public function get_user_call_id() : string {
		return $this->user_call_id != null ? $this->user_call_id : "null";
	}


	//User response to call
	public function set_status(int $status){
		$this->status = $status;
	}

	public function has_status() : bool {
		return $this->status > -1;
	}

	public function get_status() : int {
		return $this->status;
	}

}