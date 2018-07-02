<?php
/**
 * New Group Object
 * 
 * @author Pierre HUBERT
 */

class NewGroup extends BaseUniqueObjectFromUser {

    //Private properties
    private $name;

    //Set and get name
	public function set_name(string $name){
		$this->name = $name == "" ? null : $name;
	}

	public function has_name() : bool {
		return $this->name != null;
	}

	public function get_name() : string {
		return $this->name != null ? $this->name : "null";
	}
}