<?php
/**
 * Group information model
 * 
 * @author Pierre HUBERT
 */

class GroupInfo extends BaseUniqueObject {

	//Path to group logo in user data
	const PATH_GROUPS_LOGO = "groups_logo";

	//Groups visibility
	const OPEN_GROUP = 0;
	const PRIVATE_GROUP = 1;
	const SECRET_GROUP = 2;

    //Private fields
    private $name;
    private $number_members = -1;
	private $logo;
	private $membership_level = -1;
	private $visiblity = -1;
    
    
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
	
	//Get and set the URL of the logo of group
    public function set_logo(string $logo){
		$this->logo = $logo == "" ? null : $logo;
	}

	public function has_logo() : bool {
		return $this->logo != null;
	}

	public function get_logo() : string {
		return $this->logo != null ? $this->logo : self::PATH_GROUPS_LOGO."/default.png";
	}
	
	public function get_logo_url() : string {
		return path_user_data($this->get_logo());
	}

	public function get_logo_sys_path() : string {

		//For security reasons, this method is available 
		//only if the user has really a logo (avoid unattended
		//operation on default logo)
		if(!$this->has_logo())
			throw new Exception("This GroupInfo object has not any logo set!");

		return path_user_data($this->get_logo(), true);
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

	//Get and set group visibility
    public function set_visibility(int $visibility){
		$this->visibility = $visibility;
	}

	public function has_visibility() : bool {
		return $this->visibility > -1;
	}

	public function get_visibility() : int {
		return $this->visibility;
	}
}