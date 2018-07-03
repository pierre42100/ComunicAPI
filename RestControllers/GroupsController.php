<?php
/**
 * API Groups controller
 * 
 * @author Pierre HUBERT
 */

class GroupsController {
	
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
		$id = postInt("id");

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
		$id = postInt("id");

		//Get information about the group
		$group = components()->groups->get_advanced_info($id);

		//Check if the group was not found
		if(!$group->isValid())
			Rest_fatal_error(404, "The requested group was not found !");
		
		//Parse and return information about the group
		return self::AdvancedGroupInfoToAPI($group);
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
		$data["number_members"] = $info->get_number_members();

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
}