<?php
/**
 * UserLike object
 * 
 * @author Pierre HUBERT
 */

class UserLike extends BaseUniqueObjectFromUser {

	//Private fields
	private $elem_id;
	private $elem_type;

	//Set and get element ID
	public function set_elem_id(int $elem_id){
		$this->elem_id = $elem_id;
	}

	public function has_elem_id() : bool {
		return $this->elem_id > 0;
	}

	public function get_elem_id() : int {
		return $this->elem_id;
	}
	
	//Set and get elem type
	public function set_elem_type(string $elem_type){
		$this->elem_type = $elem_type == "" ? null : $elem_type;
	}

	public function has_elem_type() : bool {
		return $this->elem_type != null;
	}

	public function get_elem_type() : string {
		return $this->elem_type != null ? $this->elem_type : "null";
	}
}