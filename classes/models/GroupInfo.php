<?php
/**
 * Group information model
 * 
 * @author Pierre HUBERT
 */

class GroupInfo extends BaseUniqueObject {

    //Private fields
    private $name;
    private $number_members = -1;

    
    
    //Get and set the name of group
    public function set_name(string $name){
		$this->name = $name == "" ? null : $name;
	}

	public function has_name() : bool {
		return $this->name != null;
	}

	public function get_name() : string {
		return $this->name != null ? $this->name : "null";
    }
    
    //Get and set the number of members of the group
    public function set_number_members(int $number_members){
		$this->number_members = $number_members;
	}

	public function has_number_members() : bool {
		return $this->number_members > -1;
	}

	public function get_number_members() : int {
		return $this->number_members;
    }
}