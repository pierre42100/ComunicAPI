<?php
/**
 * Advanced information about a group model
 * 
 * @author Pierre HUBERT
 */

//Make sure that GroupInfo has already been included
require_once __DIR__."/GroupInfo.php";

class AdvancedGroupInfo extends GroupInfo {

    //Private fields
    private $time_create = -1;

    //Get and set the creation time of the group
    public function set_time_create(int $time_create){
		$this->time_create = $time_create;
	}

	public function has_time_create() : bool {
		return $this->time_create > -1;
	}

	public function get_time_create() : int {
		return $this->time_create;
    }

}