<?php
/**
 * API Groups controller
 * 
 * @author Pierre HUBERT
 */

class GroupsController {
	
	/**
	 * API groups membership levels
	 */
	const GROUPS_MEMBERSHIP_LEVELS = array(
		0 => "administrator",
		1 => "moderator",
		2 => "member",
		3 => "pending",
		4 => "visitor"
	);

	/**
	 * Create a group
	 * 
	 * @url POST /groups/create
	 */
	public function create(){

		//Login required
		user_login_required();

		//Get the name of the new group
		$name = postString("name", 3);

		//Prepare group creation
		$newGroup = new NewGroup();
		$newGroup->set_name($name);
		$newGroup->set_userID(userID);
		$newGroup->set_time_sent(time());

		//Try to create the group
		$groupID = components()->groups->create($newGroup);

		//Check for errors
		if($groupID < 1)
			Rest_fatal_error(500, "An error occurred while trying to create the group!");
		
		//Success
		return array(
			"success" => "The group has been successfully created!",
			"id" => $groupID
		);
	}

	/**
	 * Get information about a group
	 * 
	 * @url POST /groups/get_info
	 */
	public function getInfo(){

		//Get the ID of the requested group
		$id = getPostGroupId("id");

		//Get information about the group
		$group = components()->groups->get_info($id);

		//Check if the group was not found
		if(!$group->isValid())
			Rest_fatal_error(404, "The requested group was not found !");
		
		//Parse and return information about the group
		return self::GroupInfoToAPI($group);
	}

	/**
	 * Get advanced information about a group
	 * 
	 * @url POST /groups/get_advanced_info
	 */
	public function getAdvancedInfo(){

		//Get the ID of the requested group
		$id = getPostGroupId("id");

		//Get information about the group
		$group = components()->groups->get_advanced_info($id);

		//Check if the group was not found
		if(!$group->isValid())
			Rest_fatal_error(404, "The requested group was not found !");
		
		//Parse and return information about the group
		return self::AdvancedGroupInfoToAPI($group);
	}

	/**
	 * Get the settings of a group
	 * 
	 * @url POST /groups/get_settings
	 */
	public function getSettings(){

		//Get the ID of the group (with admin access)
		$groupID = $this->getPostGroupIDWithAdmin("id");

		//Retrieve the settings of the group
		$settings = components()->groups->get_settings($groupID);

		//Check for error
		if(!$settings->isValid())
			Rest_fatal_error(500, "Could not get the settings of the group!");
		
		//Return parsed settings
		return self::GroupSettingsToAPI($settings);
	}

	/**
	 * Get and return a group ID specified in the POST request
	 * in which the current user has admin rigths
	 * 
	 * @param string $name The name of the POST field
	 * @return int The ID of the group
	 */
	private function getPostGroupIDWithAdmin(string $name) : int {

		//User must be signed in
		user_login_required();

		//Get the ID of the group
		$groupID = getPostGroupId($name);

		//Check if the user is an admin of the group or not
		if(!components()->groups->isAdmin(userID, $groupID))
			Rest_fatal_error(401, "You are not an administrator of this group!");
		
		return $groupID;
	}

	/**
	 * Parse a GroupInfo object into an array for the API
	 * 
	 * @param GroupInfo $info Information about the group
	 * @return array Generated API data
	 */
	public static function GroupInfoToAPI(GroupInfo $info) : array {
		$data = array();

		$data["id"] = $info->get_id();
		$data["name"] = $info->get_name();
		$data["icon_url"] = $info->get_icon_url();
		$data["number_members"] = $info->get_number_members();
		$data["membership"] = self::GROUPS_MEMBERSHIP_LEVELS[$info->get_membership_level()];

		return $data;
	}

	/**
	 * Parse an AdvancedGroupInfo object into an array for the API
	 * 
	 * @param AdvancedGroupInfo $info Information about the group
	 * @return array Generated API data
	 */
	public static function AdvancedGroupInfoToAPI(AdvancedGroupInfo $info) : array {
		$data = self::GroupInfoToAPI($info);

		$data["time_create"] = $info->get_time_create();

		return $data;
	}

	/**
	 * Parse a GroupSettings object into an array for the API
	 * 
	 * @param GroupSettings $settings The settings to parse
	 * @return array Generated array
	 */
	public static function GroupSettingsToAPI(GroupSettings $info) : array {
		$data = self::AdvancedGroupInfoToAPI($info);

		return $data;
	}
}