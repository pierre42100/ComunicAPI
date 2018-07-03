<?php
/**
 * Group member object model
 * 
 * @author Pierre HUBERT
 */

class GroupMember extends BaseUniqueObjectFromUser {

    /**
     * Groups membership levels
     */
    const ADMINISTRATOR = 0;
    const MODERATOR = 1;
	const MEMBER = 2;
	const PENDING = 3; //When the group membership has not been approved yet

    //Private fields
    private $group_id = 1;
    private $level = -1;

    //Set and get group id
    public function set_group_id(int $group_id){
		$this->group_id = $group_id;
	}

	public function has_group_id() : bool {
		return $this->group_id > -1;
	}

	public function get_group_id() : int {
		return $this->group_id;
	}

    //Set and get user membership level
    public function set_level(int $level){
		$this->level = $level;
	}

	public function has_level() : bool {
		return $this->level > -1;
	}

	public function get_level() : int {
		return $this->level;
	}
}