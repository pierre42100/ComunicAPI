<?php
/**
 * Base user model that contains identification information
 * about the user
 * 
 * @author Pierre HUBERT
 */

class BaseUserModel {

	//Private fields
	private $userID = 0;

	//Set and get user ID
	public function set_id(int $id){
		$this->id = $id;
	}

	public function get_id() : int {
		return $this->id;
	}
	
	/**
	 * Check wether this object is valid or not
	 * 
	 * @return bool TRUE if this object is valid / FALSE else
	 */
	public function isValid() : bool {
		return $this->id > 0;
	}
}