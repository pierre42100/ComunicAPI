<?php
/**
 * Base object for any unique object
 * 
 * @author Pierre HUBERT
 */

abstract class BaseUniqueObject {

	//Private fields
	private $id = 0;

	//Set and get object ID
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