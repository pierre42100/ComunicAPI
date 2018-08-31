<?php
/**
 * API Groups controller
 * 
 * @author Pierre HUBERT
 */

class GroupsController {
	
	/**
	 * API groups registration levels
	 */
	const GROUPS_REGISTRATION_LEVELS = array(
		GroupInfo::OPEN_REGISTRATION => "open",
		GroupInfo::MODERATED_REGISTRATION => "moderated",
		GroupInfo::CLOSED_REGISTRATION => "closed"
	);

	/**
	 * API groups membership levels
	 */
	const GROUPS_MEMBERSHIP_LEVELS = array(
		GroupMember::ADMINISTRATOR => "administrator",
		GroupMember::MODERATOR => "moderator",
		GroupMember::MEMBER => "member",
		GroupMember::INVITED => "invited",
		GroupMember::PENDING => "pending",
		GroupMember::VISITOR => "visitor"
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
	 * API posts creation levels
	 */
	const GROUPS_POSTS_LEVELS = array(
		GroupInfo::POSTS_LEVEL_MODERATORS => "moderators",
		GroupInfo::POSTS_LEVEL_ALL_MEMBERS => "members"
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
		$group = components()->groups->get_info($groupID);

		//Check if the group was not found
		if(!$group->isValid())
			Rest_fatal_error(404, "The requested group was not found !");
		
		//Parse and return information about the group
		return self::GroupInfoToAPI($group);
	}

	/**
	 * Get information about multiple groups
	 * 
	 * @url POST /groups/get_multiple_info
	 */
	public function getMultipleInfo(){

		//Get the IDs of requested groups
		$IDs = numbers_list_to_array(postString("list", 1));

		//Process the list of groups
		foreach($IDs as $groupID){

			//Check if the group exists or not
			if(!components()->groups->exists($groupID))
				Rest_fatal_error(404, "Group ".$groupID." not found!");

			//Check the user is allowed to access this group information
			if(components()->groups->getAccessLevel($groupID, userID) < GroupInfo::LIMITED_ACCESS)
				Rest_fatal_error(404, "Group ".$groupID." not found!");
			
			//Get the group information
			$group = components()->groups->get_info($groupID);

			if(!$group->isValid())
				Rest_fatal_error(500, "Could not get a group information!");
			
			$IDs[$groupID] = self::GroupInfoToAPI($group);
		}

		return $IDs;
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
		$group = components()->groups->get_advanced_info($groupID);

		//Check if the group was not found
		if(!$group->isValid())
			Rest_fatal_error(404, "The requested group was not found !");
		
		//If the user is signed in, check whether he is liking and following or not the group
		if(userID > 0) {
			$group->setLiking(components()->likes->is_liking(
				userID, $group->get_id(), Likes::LIKE_GROUP));

			
			$group->set_following(components()->groups->isFollowing(
				userID, $group->get_id()
			));
		}

		//Parse and return information about the group
		return self::AdvancedGroupInfoToAPI($group);
	}

	/**
	 * Get the settings of a group
	 * 
	 * @url POST /groups/get_settings
	 */
	public function getSettings(){

		user_login_required();

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

		user_login_required();

		//Get the ID of the group (with admin access)
		$groupID = getPostGroupIdWithAccess("id", GroupInfo::ADMIN_ACCESS);

		//Create and fill a GroupSettings object with new values
		$settings = new GroupSettings();
		$settings->set_id($groupID);
		$settings->set_name(postString("name", 3));
		$settings->set_description(removeHTMLnodes(postString("description", 0)));
		
		//Get group URL
		$url = postString("url", 0);
		if($url != ""){
			if(!filter_var($url, FILTER_VALIDATE_URL))
				Rest_fatal_error(401, "Invalid group URL!");
			
			$settings->set_url($url);
		}

		//Get group visibility
		$visiblity = postString("visibility", 3);
		$levels = array_flip(self::GROUPS_VISIBILITY_LEVELS);
		if(!isset($levels[$visiblity]))
			Rest_fatal_error(400, "Unrecognized group visibility level!");
		$settings->set_visibility($levels[$visiblity]);

		//Get group registration level
		$registration_level = postString("registration_level", 3);
		$levels = array_flip(self::GROUPS_REGISTRATION_LEVELS);
		if(!isset($levels[$registration_level]))
			Reset_fatal_error(400, "Unrecognized group registration level!");
		$settings->set_registration_level($levels[$registration_level]);

		//Get group posts creation levels
		$postsLevel = postString("posts_level", 3);
		$levels = array_flip(self::GROUPS_POSTS_LEVELS);
		if(!isset($levels[$postsLevel]))
			Rest_fatal_error(400, "Unrecognized group posts level!");
		$settings->set_posts_level($levels[$postsLevel]);

		//Get and check group virtual directory
		$virtualDirectory = postString("virtual_directory", 0);
		if($virtualDirectory != ""){

			$virtualDirectory = getPostVirtualDirectory("virtual_directory");

			//Check virtual directory availability
			if(!checkVirtualDirectoryAvailability($virtualDirectory, $groupID, TRUE))
				Rest_fatal_error(401, "The virtual directory seems not to be available!");
			
			$settings->set_virtual_directory($virtualDirectory);

		}

		//Try to save the new settings of the group
		if(!components()->groups->set_settings($settings))
			Rest_fatal_error(500, "An error occured while trying to update group settings!");
		
		return array("success" => "Group settings have been successfully updated!");
	}

	/**
	 * Check the availability of a virtual directory
	 * 
	 * @url POST /groups/checkVirtualDirectory
	 */
	public function checkVirtualDirectory() : array {

		//Get the ID of the group to check
		$groupID = getPostGroupIdWithAccess("groupID", GroupMember::ADMINISTRATOR);

		//Get post virtual directory
		$virtualDirectory = getPostVirtualDirectory("directory");

		//Check virtual directory availability
		if(!checkVirtualDirectoryAvailability($virtualDirectory, $groupID, TRUE))
			Rest_fatal_error(401, "The virtual directory seems not to be available!");
		
		//The directory is available
		return array("success" => "The directory is available!");
	}

	/**
	 * Change (update) the logo of the group
	 * 
	 * @url POST /groups/upload_logo
	 */
	public function uploadLogo(){

		user_login_required();

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

		user_login_required();

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
	 * Get the entire list of the members of a group
	 * 
	 * @url POST /groups/get_members
	 */
	public function getMembers(){

		user_login_required();

		//Get the ID of the group (with admin access)
		$groupID = getPostGroupIdWithAccess("id", GroupInfo::MODERATOR_ACCESS);

		//Get the list of members of the group
		$members = components()->groups->getListMembers($groupID);

		//Parse the list of members
		foreach($members as $num => $member)
			$members[$num] = self::GroupMemberToAPI($member);

		return $members;
	}

	/**
	 * Respond to a membership invitation
	 * 
	 * @url POST /groups/respond_invitation
	 */
	public function respondInvitation(){

		user_login_required();

		//Get the ID of the group (with basic access)
		$groupID = getPostGroupIdWithAccess("id", GroupInfo::LIMITED_ACCESS);

		//Get the response to the invitation
		$accept = postBool("accept");

		//Check if the user received an invitation or not
		if(!components()->groups->receivedInvitation(userID, $groupID))
			Rest_fatal_error(404, "Invitation not found!");

		//Try to respond to the invitation
		if(!components()->groups->respondInvitation(userID, $groupID, $accept))
			Rest_fatal_error(500, "An error occurred while trying to respond to membership invitation!");
		
		//Push notification
		create_group_membership_notification(userID, 0, $groupID, 
			$accept ? Notification::ACCEPTED_GROUP_MEMBERSHIP_INVITATION : Notification::REJECTED_GROUP_MEMBERSHIP_INVITATION);

		//Success
		return array("success" => "The response to the invitation was saved!");
	}

	/**
	 * Cancel a membership request
	 * 
	 * @url POST /groups/cancel_request
	 */
	public function cancelRequest(){

		user_login_required();

		//Get the ID of the group (with basic access)
		$groupID = getPostGroupIdWithAccess("id", GroupInfo::LIMITED_ACCESS);

		//Check if the user has created a membership request
		if(components()->groups->getMembershipLevel(userID, $groupID) != GroupMember::PENDING)
			Rest_fatal_error(401, "You did not send a membership request to this group!");
		
		//Try to cancel membership request
		if(!components()->groups->deleteRequest(userID, $groupID))
			Rest_fatal_error(500, "An error occurred while trying to cancel membership request!");
		
		//Delete group membership notifications
		delete_notifications_group_membership(userID, $groupID);

		return array("success" => "The request has been successfully cancelled!");
	}

	/**
	 * Send a membership request to the server
	 * 
	 * @url POST /groups/send_request
	 */
	public function sendRequest(){

		user_login_required();

		//Get the ID of the target group
		$groupID = getPostGroupIdWithAccess("id", GroupInfo::LIMITED_ACCESS);

		//Check if the user is currently only a visitor of the website
		if(components()->groups->getMembershipLevel(userID, $groupID) != GroupMember::VISITOR)
			Rest_fatal_error(401, "You are not currently a visitor of the group!");

		//Check if the user can register a new membership to the group
		//Get information about the group
		$info = components()->groups->get_info($groupID);

		if($info->get_registration_level() == GroupInfo::CLOSED_REGISTRATION)
			Rest_fatal_error(401, "You are not authorized to send a registration request for this group!");
		
		//Create and insert membership
		$member = new GroupMember();
		$member->set_userID(userID);
		$member->set_time_sent(time());
		$member->set_group_id($groupID);
		$member->set_level(
			$info->get_registration_level() == GroupInfo::MODERATED_REGISTRATION ? 
			GroupMember::PENDING : GroupMember::MEMBER);
		if(!components()->groups->insertMember($member))
			Rest_fatal_error(500, "Could not register membership!");
		
		//Push notification
		if($info->get_registration_level() == GroupInfo::MODERATED_REGISTRATION)
			create_group_membership_notification(userID, 0, $groupID, 
				Notification::SENT_GROUP_MEMBERSHIP_REQUEST);

		//Success
		return array("success" => "The membership has been successfully saved!");
	}

	/**
	 * Delete the member from the group
	 * 
	 * @url POST /groups/delete_member
	 */
	public function deleteMember() : array {

		user_login_required();

		//Get the ID of the target group
		$groupID = getPostGroupIdWithAccess("groupID", GroupInfo::MODERATOR_ACCESS);
		$currUserLevel = components()->groups->getMembershipLevel(userID, $groupID);

		//Get the ID of the member
		$userID = getPostUserID("userID");

		if($userID == userID && $currUserLevel == GroupMember::ADMINISTRATOR){

			//Count the number of admin in the group
			if(components()->groups->countMembersAtLevel($groupID, GroupMember::ADMINISTRATOR) == 1)
				Rest_fatal_error(401, "You are the last administrator of this group!");

		}

		//Get the current membership level
		$level = components()->groups->getMembershipLevel($userID, $groupID);

		//Check if the user is more than a member. In this case, only an administrator can delete him
		if($level < GroupMember::MEMBER && $currUserLevel != GroupMember::ADMINISTRATOR)
			Rest_fatal_error(401, "Only an administrator can delete this membership!");
		
		//Delete the membership
		if(!components()->groups->deleteMembershipWithStatus($userID, $groupID, $level))
			Rest_fatal_error(500, "Could not delete membership!");
		
		//Delete group membership notifications
		delete_notifications_group_membership($userID, $groupID);

		//Success
		return array("success" => "The membership has been successfully deleted!");
	}

	/**
	 * Update a membership level
	 * 
	 * @url POST /groups/update_membership_level
	 */
	public function updateMembershipLevel() : array {

		user_login_required();

		//Get the ID of the target group
		$groupID = getPostGroupIdWithAccess("groupID", GroupInfo::ADMIN_ACCESS);

		//Get target user ID
		$userID = getPostUserID("userID");

		if($userID == userID)
			Rest_fatal_error(400, "You can not update your own membership!");

		//Get current user membership
		$level = components()->groups->getMembershipLevel($userID, $groupID);

		//Check if the user is at least a member of the group
		if($level > GroupMember::MEMBER)
			Rest_fatal_error(401, "This user is not a member of the group!");
		
		//Get the new membership level of the user
		$levels = array_flip(self::GROUPS_MEMBERSHIP_LEVELS);

		$new_level_str = postString("level");
		if(!isset($levels[$new_level_str]))
			Rest_fatal_error(401, "Specified membership level not found!");
		$newLevel = $levels[$new_level_str];
		
		if($newLevel > GroupMember::MEMBER)
			Rest_fatal_error(401, "You can not assign this visibility level to a group member!");

		//Try to update the membership of the user
		if(!components()->groups->updateMembershipLevel($userID, $groupID, $newLevel))
			Rest_fatal_error(500, "Could not update membership level!");

		//Success
		return array("success" => "User membership has been updated!");
	}

	/**
	 * Respond to a membership request
	 * 
	 * @url POST /groups/respond_request
	 */
	public function respondRequest() : array {

		user_login_required();

		//Get the ID of the target group
		$groupID = getPostGroupIdWithAccess("groupID", GroupInfo::MODERATOR_ACCESS);

		//Get user ID
		$userID = getPostUserID("userID");

		//Get the response
		$accept = postBool("accept");

		//Check if the user membership is really pending or not
		if(components()->groups->getMembershipLevel($userID, $groupID) != GroupMember::PENDING)
			Rest_fatal_error(401, "This user has not requested a membership in this group!");
		
		//Respond to the request
		if(!components()->groups->respondRequest($userID, $groupID, $accept))
			Rest_fatal_error(500, "Could not respond to the membership request!");
		
		//Push notification
		create_group_membership_notification($userID, userID, $groupID, 
			$accept ? Notification::ACCEPTED_GROUP_MEMBERSHIP_REQUEST : Notification::REJECTED_GROUP_MEMBERSHIP_REQUEST);

		//Success
		return array("success" => "The response to the request has been successfully saved!");
	}

	/**
	 * Get information about a membership
	 * 
	 * @url POST /groups/get_membership
	 */
	public function getMembership() : array {

		//Get the ID of the target group
		$groupID = getPostGroupIdWithAccess("groupID", GroupInfo::MODERATOR_ACCESS);

		//Get user ID
		$userID = getPostUserID("userID");

		//Check if the user has a membership or not
		if(!components()->groups->hasMembership($userID, $groupID))
			Rest_fatal_error(404, "Specified user does not have any membership in this group!");

		//Get user membership
		$membership = components()->groups->getMembership($userID, $groupID);

		//Parse and return result
		return self::GroupMemberToAPI($membership);
	}

	/**
	 * Cancel a membership invitation
	 * 
	 * @url POST /groups/cancel_invitation
	 */
	public function cancelInvitation() : array {

		//Get the ID of the target group
		$groupID = getPostGroupIdWithAccess("groupID", GroupInfo::MODERATOR_ACCESS);

		//Get user ID
		$userID = getPostUserID("userID");

		//Check if the user has really been invited to the group or not
		if(components()->groups->getMembershipLevel($userID, $groupID) != GroupMember::INVITED)
			Rest_fatal_error(401, "This user has not been invited to join this group!");
		
		//Cancel group invitation
		if(!components()->groups->deleteInvitation($userID, $groupID))
			Rest_fatal_error(500, "Could not cancel membership invitation!");

		//Delete group membership notifications
		delete_notifications_group_membership($userID, $groupID);

		//Success
		return array("success" => "Membership invitation has been cancelled !");
	}

	/**
	 * Get the list of membership of the current user
	 * 
	 * @url POST groups/get_my_list
	 */
	public function getMyList() : array {

		user_login_required();

		//Get the list of groups of the user
		$list = components()->groups->getListUser(userID);

		//Parse list
		foreach($list as $num => $info)
			$list[$num] = self::GroupInfoToAPI($info);
		
		return $list;

	}

	/**
	 * Delete a user membership to a group
	 * 
	 * @url POST groups/remove_membership
	 */
	public function removeMembership() : array {
		user_login_required();

		//Get the group
		$groupID = getPostGroupIdWithAccess("id", GroupInfo::LIMITED_ACCESS);

		//Get user membership level
		$level = components()->groups->getMembershipLevel(userID, $groupID);

		if($level == GroupMember::ADMINISTRATOR){

			//Check the user is not the last administrator of the page
			if(components()->groups->countMembersAtLevel($groupID, GroupMember::ADMINISTRATOR) == 1)
				Rest_fatal_error(401, "You are the latest administrator of the group!");

		}

		//Delete membership
		if(!components()->groups->deleteMembershipWithStatus(userID, $groupID, $level))
			Rest_fatal_error(500, "An error occurred while trying to delete your membership!");
		
		//Delete group membership notifications
		delete_notifications_group_membership(userID, $groupID);

		//Success
		return array("success" => "Your membership has been successfully deleted!");
	}

	/**
	 * Set whether a user is following a group or not
	 * 
	 * @url POST groups/set_following
	 */
	public function setFollowing(){
		user_login_required();

		//Get the group
		$groupID = getPostGroupIdWithAccess("groupID", GroupInfo::MEMBER_ACCESS);

		//Get following status
		$following = postBool("follow");

		//Save the new value
		if(!components()->groups->setFollowing($groupID, userID, $following))
			Rest_fatal_error(500, "Could not update following status!");
		
		//Success
		return array("success" => "Follow status has been successfully updated!");
	}

	/**
	 * Delete a group
	 * 
	 * @url POST groups/delete
	 */
	public function delete(){

		user_login_required();

		//Check user password
		if(!check_post_password(userID, "password"))
			Rest_fatal_error(401, "Password required!");

		//Get the group
		$groupID = getPostGroupIdWithAccess("groupID", GroupInfo::ADMIN_ACCESS);

		//Delete the group
		if(!components()->groups->delete_group($groupID))
			Rest_fatal_error(500, "Could not delete group!");
		
		return array("success" => "The group has been successfully deleted!");
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
		$data["name"] = removeHTMLnodes($info->get_name());
		$data["icon_url"] = $info->get_logo_url();
		$data["number_members"] = $info->get_number_members();
		$data["membership"] = self::GROUPS_MEMBERSHIP_LEVELS[$info->get_membership_level()];
		$data["visibility"] = self::GROUPS_VISIBILITY_LEVELS[$info->get_visibility()];
		$data["registration_level"] = self::GROUPS_REGISTRATION_LEVELS[$info->get_registration_level()];
		$data["posts_level"] = self::GROUPS_POSTS_LEVELS[$info->get_posts_level()];
		$data["virtual_directory"] = $info->get_virtual_directory();
		$data["following"] = $info->isFollowing();

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
		$data["description"] = $info->get_description();
		$data["url"] = $info->get_url();
		$data["number_likes"] = $info->get_number_likes();
		$data["is_liking"] = $info->isLiking();

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

	/**
	 * Turn GroupMember oject into an API array
	 * 
	 * @param GroupMember $member The member entry to convert
	 * @return array Generated entry
	 */
	public static function GroupMemberToAPI(GroupMember $member) : array {
		$data = array();

		$data["user_id"] = $member->get_userID();
		$data["group_id"] = $member->get_group_id();
		$data["time_create"] = $member->get_time_sent();
		$data["level"] = self::GROUPS_MEMBERSHIP_LEVELS[$member->get_level()];

		return $data;
	}
}