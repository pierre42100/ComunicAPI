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
		GroupInfo::ADMINISTRATOR => "administrator",
		GroupInfo::MODERATOR => "moderator",
		GroupInfo::MEMBER => "member",
		GroupInfo::PENDING => "pending",
		GroupInfo::VISITOR => "visitor"
	);

	/**
	 * API groups visibility levels
	 */
	const GROUPS_VISIBILITY_LEVELS = array(
		GroupInfo::OPEN_GROUP => "open",
		GroupInfo::PRIVATE_GROUP => "private",
		GroupInfo::SECRET_GROUP => "secrete"
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
		$groupID = getPostGroupIdWithAccess("id", GroupInfo::LIMITED_ACCESS);

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
		$groupID = getPostGroupIdWithAccess("id", GroupInfo::VIEW_ACCESS);

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
		$groupID = getPostGroupIdWithAccess("id", GroupInfo::ADMIN_ACCESS);

		//Retrieve the settings of the group
		$settings = components()->groups->get_settings($groupID);

		//Check for error
		if(!$settings->isValid())
			Rest_fatal_error(500, "Could not get the settings of the group!");
		
		//Return parsed settings
		return self::GroupSettingsToAPI($settings);
	}

	/**
	 * Set (update) the settings of a group
	 * 
	 * @url POST /groups/set_settings
	 */
	public function setSettings(){

		//Get the ID of the group (with admin access)
		$groupID = getPostGroupIdWithAccess("id", GroupInfo::ADMIN_ACCESS);

		//Create and fill a GroupSettings object with new values
		$settings = new GroupSettings();
		$settings->set_id($groupID);
		$settings->set_name(postString("name", 3));

		//Get group visibility
		$visiblity = postString("visibility", 3);
		$levels = array_flip(self::GROUPS_VISIBILITY_LEVELS);
		if(!isset($levels[$visiblity]))
			Rest_fatal_error(400, "Unrecognized group visibility level!");
		$settings->set_visibility($levels[$visiblity]);

		//Try to save the new settings of the group
		if(!components()->groups->set_settings($settings))
			Rest_fatal_error(500, "An error occured while trying to update group settings!");
		
		return array("success" => "Group settings have been successfully updated!");
	}

	/**
	 * Change (update) the logo of the group
	 * 
	 * @url POST /groups/upload_logo
	 */
	public function uploadLogo(){

		//Get the ID of the group (with admin access)
		$groupID = getPostGroupIdWithAccess("id", GroupInfo::ADMIN_ACCESS);

		//Check if it is a valid file
		if(!check_post_file("logo"))
			Rest_fatal_error(400, "An error occurred while receiving logo !");
		
		//Delete any previous logo
		if(!components()->groups->deleteLogo($groupID))
			Rest_fatal_error(500, "An error occurred while trying to delete previous group logo!");
		
		//Save the new group logo
		$file_path = save_post_image("logo", 0, GroupInfo::PATH_GROUPS_LOGO, 500, 500);

		//Update the settings of the group
		$settings = components()->groups->get_settings($groupID);
		$settings->set_logo($file_path);
		
		if(!components()->groups->set_settings($settings))
			Rest_fatal_error(500, "Could not save information about new group logo!");
		
		//Success
		return array(
			"success" => "The new group logo has been successfully saved !",
			"url" => $settings->get_logo_url()
		);
	}

	/**
	 * Delete a group logo
	 * 
	 * @url POST /groups/delete_logo
	 */
	public function deleteLogo(){

		//Get the ID of the group (with admin access)
		$groupID = getPostGroupIdWithAccess("id", GroupInfo::ADMIN_ACCESS);

		//Try to delete group logo
		if(!components()->groups->deleteLogo($groupID))
			Rest_fatal_error(500, "An error occurred while trying to delete group logo!");
		
		//Success
		return array(
			"success" => "The group logo has been successfully deleted!",
			"url" => components()->groups->get_settings($groupID)->get_logo_url()
		);
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
		$data["icon_url"] = $info->get_logo_url();
		$data["number_members"] = $info->get_number_members();
		$data["membership"] = self::GROUPS_MEMBERSHIP_LEVELS[$info->get_membership_level()];
		$data["visibility"] = self::GROUPS_VISIBILITY_LEVELS[$info->get_visibility()];

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