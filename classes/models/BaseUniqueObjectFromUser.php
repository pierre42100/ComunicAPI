<?php
/**
 * Unique object with an ID created by a user base class
 * 
 * @author Pierre HUBERT
 */

class BaseUniqueObjectFromUser extends BaseUniqueObject {

	//Private fields
	private $userID = 0;
	private $time_sent;

	//Set and get user ID
	public function set_userID(int $userID){
		$this->userID = $userID;
	}

	public function get_userID() : int {
		return $this->userID;
	}

	//Set and get creation time
	public function set_time_sent(int $time_sent){
		$this->time_sent = $time_sent;
	}

	public function get_time_sent() : int {
		return $this->time_sent;
	}

}