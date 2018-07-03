<?php
/**
 * Group information model
 * 
 * @author Pierre HUBERT
 */

class GroupInfo extends BaseUniqueObject {

	//Path to group icons in user data
	const PATH_GROUPS_ICON = "groups_icon/";

    //Private fields
    private $name;
    private $number_members = -1;
	private $icon;
	private $membership_level = -1;
    
    
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
	
	//Get and set the URL of the icon of group
    public function set_icon(string $icon){
		$this->icon = $icon == "" ? null : $icon;
	}

	public function has_icon() : bool {
		return $this->icon != null;
	}

	public function get_icon() : string {
		return $this->icon != null ? $this->icon : self::PATH_GROUPS_ICON."default.png";
	}
	
	public function get_icon_url() : string {
		return path_user_data($this->get_icon());
	}
	
	//Get and set the membership level of the current user
	public function set_membership_level(int $membership_level){
		$this->membership_level = $membership_level;
	}

	public function has_membership_level() : bool {
		return $this->membership_level > -1;
	}

	public function get_membership_level() : int {
		return $this->membership_level;
	}
}