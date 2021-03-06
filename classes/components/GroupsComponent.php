<?php
/**
 * Groups component
 * 
 * @author Pierre HUBERT
 */

class GroupsComponent {

	/**
	 * Groups list table
	 */
	const GROUPS_LIST_TABLE = DBprefix . "groups";

	/**
	 * Groups members table
	 */
	const GROUPS_MEMBERS_TABLE = DBprefix."groups_members";

	/**
	 * Create a new group
	 * 
	 * @param NewGroup $newGroup Information about the new group
	 * to create
	 * @return int The ID of the created group / -1 in case of failure
	 */
	public function create(NewGroup $newGroup) : int {

		//Insert the group in the database
		db()->addLine(self::GROUPS_LIST_TABLE, array(
			"time_create" => $newGroup->get_time_sent(),
			"userid_create" => $newGroup->get_userID(),
			"name" => $newGroup->get_name()
		));

		//Get the ID of the last inserted group
		$groupID = db()->getLastInsertedID();

		//Check for errors
		if(!$groupID > 0)
			return -1;

		//Register the user who created the group as an admin of the group
		$member = new GroupMember;
		$member->set_group_id($groupID);
		$member->set_userID($newGroup->get_userID());
		$member->set_time_sent($newGroup->get_time_sent());
		$member->set_level(GroupMember::ADMINISTRATOR);
		$this->insertMember($member);

		return $groupID;
	}

	/**
	 * Check whether a group exists or not
	 * 
	 * @param int $id The ID of the target group
	 * @return bool TRUE if the group exists / FALSE else
	 */
	public function exists(int $id) : bool {

		return db()->count(
			self::GROUPS_LIST_TABLE,
			"WHERE id = ?",
			array($id)
		) > 0;

	}

	/**
	 * Get the list of groups of a user
	 * 
	 * @param int $userID The ID of the target user
	 * @return array The list of groups of the user (the IDs of the groups)
	 */
	public function getListUser(int $userID) : array {

		//First, get IDs of the groups the user belongs to
		$groups = db()->select(
			self::GROUPS_MEMBERS_TABLE,
			"WHERE user_id = ?",
			array($userID),
			array("groups_id")
		);

		//Parse results
		$info = array();
		foreach($groups as $group)
			$info[] = $group["groups_id"];
		
		return $info;
	}

	/**
	 * Get the list of groups of a user where the users can create
	 * posts
	 * 
	 * @param int $userID The ID of the target user
	 * @return array The list of the groups the user can participate to
	 */
	public function getListUserWhereCanCreatePosts(int $userID) : array {
		$list = db()->select(self::GROUPS_MEMBERS_TABLE." m, ".self::GROUPS_LIST_TABLE." g",
			"WHERE user_id = ? 
				AND m.groups_id = g.id 
				AND (
					level = ".GroupMember::ADMINISTRATOR." OR
					level = ".GroupMember::MODERATOR." OR
					(level = ".GroupMember::MEMBER." AND posts_level = ".GroupInfo::POSTS_LEVEL_ALL_MEMBERS.")
				)
			", 
			array($userID), 
			array("g.id"));

		foreach($list as $num => $info)
			$list[$num] = (int)$info["id"];

		return $list;
	}

	/**
	 * Get the visibility level of a group
	 * 
	 * @param int $id The ID of the target group
	 * @return int The visibility level of the group
	 */
	public function getVisiblity(int $id) : int {
		$data = db()->select(
			self::GROUPS_LIST_TABLE,
			"WHERE id = ?",
			array($id),
			array("visibility")
		);

		if(count($data) < 1)
			throw new Exception("Group " . $id . " does not exists!");
		
		return $data[0]["visibility"];
	}

	/**
	 * Find a group by its virtual directory
	 * 
	 * @param string $directory The directory to search
	 * @return int The ID of the target group / 0 if none found
	 */
	public function findByVirtualDirectory(string $directory) : int {
		
		$data = db()->select(
			self::GROUPS_LIST_TABLE,
			"WHERE virtual_directory = ?",
			array($directory),
			array("id")
		);

		if(count($data) == 0)
			return 0;
		else
			return $data[0]["id"];
	}

	/**
	 * Get and return information about a group
	 * 
	 * @param int $id The ID of the target group
	 * @return GroupInfo Information about the group / invalid
	 * object in case of failure
	 */
	public function get_info(int $id) : GroupInfo {

		//Query the database
		$info = db()->select(self::GROUPS_LIST_TABLE, "WHERE id = ?", array($id));

		//Check for results
		if(count($info) == 0)
			return new GroupInfo(); //Return invalid object
		
		//Create and fill GroupInfo object with database entry
		return $this->dbToGroupInfo($info[0]);
	}

	/**
	 * Get and return advanced information about a group
	 * 
	 * @param int $id The ID of the target group
	 * @return GroupInfo Information about the group / invalid
	 * object in case of failure
	 */
	public function get_advanced_info(int $id) : AdvancedGroupInfo {

		//Query the database
		$info = db()->select(self::GROUPS_LIST_TABLE, "WHERE id = ?", array($id));

		//Check for results
		if(count($info) == 0)
			return new AdvancedGroupInfo(); //Return invalid object
		
		//Create and fill GroupInfo object with database entry
		return $this->dbToAdvancedGroupInfo($info[0], null, TRUE);
	}

	/**
	 * Get the timestamp of the estimated last activity on the group
	 * 
	 * @param int $id The ID of the target group
	 * @return int The time of last activity on the group
	 */
	public function getLastActivity(int $id) : int {

		// Query the database
		$posts = components()->posts->getGroupPosts($id, true, 0, 1);

		if(count($posts) == 0)
			return 0;

		else
			return $posts[0]->get_time_sent();

	}

	/**
	 * Get a group settings
	 * 
	 * @param int $id The ID of the target group
	 * @return GroupSettings The settings of the group / invalid
	 * GroupSettings object in case of failure
	 */
	public function get_settings(int $id) : GroupSettings {

		//Query the database
		$info = db()->select(self::GROUPS_LIST_TABLE, "WHERE id = ?", array($id));

		//Check for results
		if(count($info) == 0)
			return new GroupSettings(); //Return invalid object
		
		//Create and fill GroupInfo object with database entry
		return $this->dbToGroupSettings($info[0]);

	}

	/**
	 * Set (update) group settings
	 * 
	 * @param GroupSettings $settings The settings to update
	 * @return bool TRUE for a success / FALSE
	 */
	public function set_settings(GroupSettings $settings) : bool {
		
		//Generate database entry
		$modif = $this->GroupSettingsToDB($settings);

		//Apply update
		return db()->updateDB(
			self::GROUPS_LIST_TABLE, 
			"id = ?",
			$modif,
			array($settings->get_id()));

	}

	/**
	 * Get the list of members of a group
	 * 
	 * @param int $groupID The ID of the group to fetch
	 * @return array The list of members of the group
	 */
	public function getListMembers(int $groupID) : array {

		$members = db()->select(
			self::GROUPS_MEMBERS_TABLE,
			"WHERE groups_id = ?",
			array($groupID)
		);

		//Process the list of results
		return $this->multipleDBToGroupMember($members);
	}

	/**
	 * Get the list of members of the group that follows it
	 * 
	 * @param int $groupID The ID of the target group
	 * @return array The list of members
	 */
	public function getListFollowers(int $groupID) : array {

		$result = db()->select(
			self::GROUPS_MEMBERS_TABLE,
			"WHERE groups_id = ? AND following = 1",
			array($groupID),
			array("user_id")
		);

		//Parse the list of IDs
		$list = array();
		foreach($result as $el)
			$list[] = $el["user_id"];
		return $list;

	}

	/**
	 * Get the list of groups a user is following
	 * 
	 * @param int $userID The ID of the target group
	 * @return array The IDs of the groups followed by the user
	 */
	public function getListFollowedByUser(int $userID) : array {

		$result = db()->select(
			self::GROUPS_MEMBERS_TABLE,
			"WHERE user_id = ? AND following = 1",
			array($userID),
			array("groups_id")
		);

		//Parse the list of IDs
		$list = array();
		foreach($result as $el)
			$list[] = $el["groups_id"];
		return $list;

	}

	/**
	 * Count the number of a kind of membership in a group
	 * 
	 * @param int $groupID The ID of the target group
	 * @param int $level The membership level to count
	 * @return int The number of administrators of the group
	 */
	public function countMembersAtLevel(int $groupID, int $level) : int {
		return db()->count(
			self::GROUPS_MEMBERS_TABLE,
			"WHERE groups_id = ? AND level = ?",
			array($groupID, $level)
		);
	}

	/**
	 * Insert a new group member
	 * 
	 * @param GroupMember $member Information about the member to insert
	 * @return bool TRUE for a success / FALSE else
	 */
	public function insertMember(GroupMember $member) : bool {
		return db()->addLine(self::GROUPS_MEMBERS_TABLE, array(
			"groups_id" => $member->get_group_id(),
			"user_id" => $member->get_userID(),
			"time_create" => $member->get_time_sent(),
			"level" => $member->get_level()
		));
	}

	/**
	 * Update a membership level
	 * 
	 * @param int $userID The ID of the target user
	 * @param int $groupID The ID of the related group
	 * @param int $level The target level
	 * @return bool TRUE for a success / FALSE else
	 */
	public function updateMembershipLevel(int $userID, int $groupID, int $level) : bool {
		return db()->updateDB(
			self::GROUPS_MEMBERS_TABLE,
			"user_id = ? AND groups_id = ?",
			array("level" => $level),
			array($userID, $groupID)
		);
	}

	/**
	 * Check whether a user has already a saved membership in a group or not
	 * 
	 * @param int $userID The ID of the target user
	 * @param int $groupID The ID of the target group
	 * @return bool TRUE if the database includes a membership for the user / FALSE else
	 */
	public function hasMembership(int $userID, int $groupID) : bool {
		return db()->count(
			self::GROUPS_MEMBERS_TABLE,
			"WHERE groups_id = ? AND user_id = ?", 
			array($groupID, $userID)) > 0;
	}

	/**
	 * Delete a user membership with a precise status
	 * 
	 * @param int $userID Target user ID
	 * @param int $groupID Target group
	 * @param int $status The status of the membership to delete
	 * @return bool TRUE for a success / FALSE else
	 */
	public function deleteMembershipWithStatus(int $userID, int $groupID, int $status) : bool {
		return db()->deleteEntry(
			self::GROUPS_MEMBERS_TABLE,
			"groups_id = ? AND user_id = ? AND level = ?",
			array($groupID, $userID, $status)
		);
	}

	/**
	 * Invite a user to join a group
	 * 
	 * @param int $userID The ID of the target user
	 * @param int $groupID The ID of the target group
	 * @param bool TRUE for a success / FALSE else
	 */
	public function sendInvitation(int $userID, int $groupID) : bool {
		$member = new GroupMember();
		$member->set_userID($userID);
		$member->set_group_id($groupID);
		$member->set_time_sent(time());
		$member->set_level(GroupMember::INVITED);
		return $this->insertMember($member);
	}

	/**
	 * Check whether a user received an invitation or not
	 * 
	 * @param int $userID The ID of the user to check
	 * @param int $groupID The ID of the related group
	 * @return bool TRUE if the user received an invitation / FALSE else
	 */
	public function receivedInvitation(int $userID, int $groupID) : bool {
		return db()->count(
			self::GROUPS_MEMBERS_TABLE,
			"WHERE groups_id = ? AND user_ID = ? AND level = ?",
			array($groupID, $userID, GroupMember::INVITED)
		) > 0;
	}

	/**
	 * Respond to a membership invitation
	 * 
	 * @param int $userID The ID of the target user
	 * @param int $groupID The ID of the related group
	 * @param bool $accept Set wether the user accept the invitation or not
	 * @return bool TRUE for a success / FALSE else
	 */
	public function respondInvitation(int $userID, int $groupID, bool $accept) : bool {

		//If the user reject the invitation, delete it
		if(!$accept)
			return $this->deleteInvitation($userID, $groupID);

		//Upgrade the user as member
		return $this->updateMembershipLevel($userID, $groupID, GroupMember::MEMBER);
	}

	/**
	 * Respond to a membership request
	 * 
	 * @param int $userID The ID of the target user
	 * @param int $groupID The ID of the related group
	 * @param bool $accept Set whether the request was accepted or not
	 * @return bool TRUE for a success / FALSE else
	 */
	public function respondRequest(int $userID, int $groupID, bool $accept) : bool {

		//If the user reject the invitation, delete it
		if(!$accept)
			return $this->deleteRequest($userID, $groupID);

		//Upgrade the user as member
		return $this->updateMembershipLevel($userID, $groupID, GroupMember::MEMBER);
	}

	/**
	 * Delete a membership invitation
	 * 
	 * @param int $userID The ID of the target user
	 * @param int $groupID The ID of the related group
	 * @return bool TRUE for a success / FALSE else
	 */
	public function deleteInvitation(int $userID, int $groupID) : bool {
		return $this->deleteMembershipWithStatus($userID, $groupID, GroupMember::INVITED);
	}

	/**
	 * Delete a membership request
	 * 
	 * @param int $userID The ID of the target user
	 * @param int $groupID The ID of the related group
	 * @return bool TRUE for a success / FALSE else
	 */
	public function deleteRequest(int $userID, int $groupID) : bool {
		return $this->deleteMembershipWithStatus($userID, $groupID, GroupMember::PENDING);
	}

	/**
	 * Get the membership level of a user to a group
	 * 
	 * @param int $userID The ID of the queried user
	 * @param int $groupID The ID of the target group
	 * @return int The membership level of the user
	 */
	public function getMembershipLevel(int $userID, int $groupID) : int {

		//Check for membership
		if(!$this->hasMembership($userID, $groupID))
			return GroupMember::VISITOR;
		
		//Fetch the database to get membership
		$results = db()->select(
			self::GROUPS_MEMBERS_TABLE,
			"WHERE groups_id = ? AND user_id = ?",
			array($groupID, $userID),
			array("level")
		);

		//Check for results
		if(count($results) < 0)
			return GroupMember::VISITOR; //Security first
		
		return $results[0]["level"];
	}

	/**
	 * Get information the membership of a user over a group
	 * 
	 * @param int $userID The ID of the target user
	 * @param int $groupID The ID of the target group
	 * @param GroupMember User membership
	 */
	public function getMembership(int $userID, int $groupID) : GroupMember {
		//Fetch the database to get membership
		$results = db()->select(
			self::GROUPS_MEMBERS_TABLE,
			"WHERE groups_id = ? AND user_id = ?",
			array($groupID, $userID)
		);

		//Check for results
		if(count($results) < 0)
			return new GroupMember(); //Invalid object
		
		return $this->dbToGroupMember($results[0]);
	}

	/**
	 * Determine whether a user is following or not a group
	 * 
	 * @param int $userID Target user ID
	 * @param int $groupID The ID of the related group
	 * @return bool TRUE if the user is following the group / FALSE else
	 */
	public function isFollowing(int $userID, int $groupID) : bool {
		return db()->count(
			self::GROUPS_MEMBERS_TABLE,
			"WHERE groups_id = ? AND user_ID = ? AND following = 1",
			array($groupID, $userID)
		) > 0;
	}

	/**
	 * Check whether a user is an administrator of a group
	 * or not
	 * 
	 * @param int $userID Requested user ID to check
	 * @param int $groupID Requested group to check
	 * @return bool TRUE if the user is an admin / FALSE else
	 */
	public function isAdmin(int $userID, int $groupID) : bool {
		return $this->getMembershipLevel($userID, $groupID)
			 == GroupMember::ADMINISTRATOR;
	}

	/**
	 * Check out whether a user is the last administrator of a group
	 * or not
	 * 
	 * @param int $userID The ID of the user to check
	 * @param int $groupID The ID of the target group
	 * @return bool TRUE if the user is an admin and the last one of the group
	 * and FALSE else
	 */
	public function isLastAdmin(int $userID, int $groupID) : bool {
		return $this->isAdmin($userID, $groupID) 
			&& $this->countMembersAtLevel($groupID, GroupMember::ADMINISTRATOR) === 1;
	}

	/**
	 * Check whether a group is open or not
	 * 
	 * @param int $groupID The ID of the target group
	 * @return bool TRUE if the group is open / FALSE else
	 */
	public function isOpen(int $groupID) : bool {
		return db()->count(
			self::GROUPS_LIST_TABLE,
			"WHERE id = ? AND visibility = ?",
			array($groupID, GroupInfo::OPEN_GROUP)) > 0;
	}

	/**
	 * Check whether a group is secret or not
	 * 
	 * @param int $groupID The ID of the target group
	 * @return bool TRUE if the group is open / FALSE else
	 */
	public function isSecret(int $groupID) : bool {
		return db()->count(
			self::GROUPS_LIST_TABLE,
			"WHERE id = ? AND visibility = ?",
			array($groupID, GroupInfo::SECRET_GROUP)) > 0;
	}

	/**
	 * Count the number of members of a group
	 * 
	 * @param int $id The ID of the target group
	 * @return int The number of members of the group
	 */
	private function countMembers(int $id) : int {
		return db()->count(self::GROUPS_MEMBERS_TABLE, 
			"WHERE groups_id = ? AND level <= ".GroupMember::MEMBER,
			array($id));
	}

	/**
	 * Get and return the access level of a user over a group
	 * 
	 * @param int $groupID The ID of the target group
	 * @param int $userID The ID of the user
	 * @return int The visiblity access level of the user
	 */
	public function getAccessLevel(int $groupID, int $userID) : int {

		if($userID > 0)
			//Get the membership level of the user
			$membership_level = $this->getMembershipLevel($userID, $groupID);
		
		else
			$membership_level = GroupMember::VISITOR; //Signed out users are all visitors

		//Check if the user is a confirmed member of group
		if($membership_level == GroupMember::ADMINISTRATOR)
			return GroupInfo::ADMIN_ACCESS;
		if($membership_level == GroupMember::MODERATOR)
			return GroupInfo::MODERATOR_ACCESS;
		if($membership_level == GroupMember::MEMBER)
			return GroupInfo::MEMBER_ACCESS;
		
		//Get the visibility level of the group
		$group_visibility_level = $this->getVisiblity($groupID);

		//If the group is open, everyone has view access
		if($group_visibility_level == GroupInfo::OPEN_GROUP)
			return GroupInfo::VIEW_ACCESS;

		//Else, all pending and invited membership get limited access
		if($membership_level == GroupMember::PENDING ||
			$membership_level == GroupMember::INVITED)
			return GroupInfo::LIMITED_ACCESS;

		//Private groups gives limited access
		if($group_visibility_level == GroupInfo::PRIVATE_GROUP)
			return GroupInfo::LIMITED_ACCESS;
		
		//Else the user can not see the group
		return GroupInfo::NO_ACCESS;
	}

	/**
	 * Check whether a user can create posts or not on a group
	 * 
	 * @param int $userID The related user ID
	 * @param int $groupID The ID of the target group
	 * @return bool TRUE if the user is authorized / FALSE else
	 */
	public function canUserCreatePost(int $userID, int $groupID) : bool {

		//Get the membership level of the user over the post
		$membership_level = $this->getMembershipLevel($userID, $groupID);

		//Moderators + administrators : can always create posts
		if($membership_level == GroupMember::ADMINISTRATOR
			|| $membership_level == GroupMember::MODERATOR)

			return TRUE;
		
		if($membership_level == GroupMember::MEMBER) {

			//Get information about the group to check whether all the members of
			//the group are authorized to create posts or not
			$group = $this->get_advanced_info($groupID);

			if($group->get_posts_level() == GroupInfo::POSTS_LEVEL_ALL_MEMBERS)
				return TRUE;

		}
		
		//Other members can not create posts
		return FALSE;

	}

	/**
	 * Delete current group logo (if any)
	 * 
	 * @param int $id The ID of the target group
	 * @return bool TRUE if the logo was deleted / FALSE else
	 */
	public function deleteLogo(int $id) : bool {

		//Get the current settings of the group
		$settings = $this->get_settings($id);

		//Check if the group has currently an group logo or not
		if($settings->has_logo()){
			
			//Delete the previous logo
			if(file_exists($settings->get_logo_sys_path()))
				if(!unlink($settings->get_logo_sys_path()))
					return FALSE;
			
			//Save new information
			$settings->set_logo("null");
			return $this->set_settings($settings);
		}
		
		//Success (nothing to be done)
		return TRUE;
	}

	/**
	 * Check whether a directory is available or not
	 * 
	 * @param string $directory The directory to check
	 * @param int $groupID The ID of the target group
	 * @return bool TRUE if the directory is available / FALSE
	 */
	public function checkDirectoryAvailability(string $directory, int $groupID) : bool {
		$currID = $this->findByVirtualDirectory($directory);

		//Check if the domain has not been allocated
		if($currID < 1)
			return TRUE;
		
		else
			//Else check if the directory has been allocated to the current user 
			return $groupID == $currID;
	}

	/**
	 * Set (update) user following status
	 * 
	 * @param int $groupID Target group ID
	 * @param int $userID Target user ID
	 * @param bool $following New following status
	 * @return bool TRUE to follow / FALSE else
	 */
	public function setFollowing(int $groupID, int $userID, bool $following) : bool {
		return db()->updateDB(
			self::GROUPS_MEMBERS_TABLE, 
			"groups_id = ? AND user_id = ?",
			array("following" => $following ? 1 : 0),
			array($groupID, $userID));
	}

	/**
	 * Delete a group
	 * 
	 * @param int $groupID Target group id
	 * @return bool TRUE for a success / FALSE else
	 */
	public function delete_group(int $groupID) : bool {

		// Delete all the likes of the group
		if(!components()->likes->delete_all($groupID, Likes::LIKE_GROUP))
			return FALSE;

		//Delete group image
		if(!$this->deleteLogo($groupID))
			return FALSE;

		//Delete all group posts
		if(!components()->posts->deleteAllGroup($groupID))
			return FALSE;

		//Delete all group related notifications
		if(!components()->notifications->deleteAllRelatedWithGroup($groupID))
		 	return FALSE;

		//Delete all group members
		if(!db()->deleteEntry(self::GROUPS_MEMBERS_TABLE, "groups_id = ?", array($groupID)))
			return FALSE;

		//Delete group information
		if(!db()->deleteEntry(self::GROUPS_LIST_TABLE, "id = ?", array($groupID)))
			return FALSE;
		
		//Success
		return TRUE;
	}

	/**
	 * Delete all the groups a user belongs to 
	 * 
	 * @param int $userID The ID of the target user
	 * @return bool TRUE in case of success / FALSE else
	 */
	public function deleteAllUsersGroups(int $userID) : bool {
		
		//Get all user gropus
		foreach($this->getListUser($userID) as $groupID){

			//Get information about user membership to determine whether the group has to be
			// deleted or not, to do so we check whether the user is the last administrator
			// of the group or not
			if($this->isLastAdmin($userID, $groupID)) {
				if(!$this->delete_group($groupID))
					return FALSE;
			}
			else
				//Make the user leave the group
				if(!$this->deleteMembershipWithStatus(
					$userID, $groupID, $this->getMembershipLevel($userID, $groupID)))
					return FALSE;

		}

		//Success
		return TRUE;
	}

	/**
	 * Turn a database entry into a GroupInfo object
	 * 
	 * @param array $data Database entry
	 * @param GroupInfo $group The object to fill with the information (optionnal)
	 * @return GroupInfo Generated object
	 */
	private function dbToGroupInfo(array $data, GroupInfo $info = null) : GroupInfo {

		if($info == null)
			$info = new GroupInfo();

		$info->set_id($data["id"]);
		$info->set_name($data["name"]);
		$info->set_number_members($this->countMembers($info->get_id()));
		$info->set_membership_level($this->getMembershipLevel(userID, $info->get_id()));
		$info->set_visibility($data["visibility"]);
		$info->set_registration_level($data["registration_level"]);
		$info->set_posts_level($data["posts_level"]);

		if($data["path_logo"] != null && $data["path_logo"] != "" && $data["path_logo"] != "null")
			$info->set_logo($data["path_logo"]);
		
		if($data["virtual_directory"] != null && $data["virtual_directory"] != "" && $data["virtual_directory"] != "null")
			$info->set_virtual_directory($data["virtual_directory"]);

		return $info;

	}

	/**
	 * Turn a database group entry into AdvancedGroupInfo object entry
	 * 
	 * @param array $data Database entry
	 * @param AdvancedGroupInfo $info Optionnal, fill an existing object
	 * instead of creating a new one
	 * @param bool $load_likes Specified whether the likes of the group should
	 * be loaded or not (default: FALSE)
	 * @return AdvancedGroupInfo Advanced information about the group
	 */
	private function dbToAdvancedGroupInfo(array $data, AdvancedGroupInfo $info = null, bool $load_likes = FALSE) : AdvancedGroupInfo {

		if($info == null)
			$info = new AdvancedGroupInfo();

		//Parse basical information about the group
		$this->dbToGroupInfo($data, $info);

		//Parse advanced information
		$info->set_time_create($data["time_create"]);
		if($data["description"] != null && $data["description"] != "" && $data["description"] != "null")
			$info->set_description($data["description"]);
		if($data["url"] != null && $data["url"] != "" && $data["url"] != "null")
			$info->set_url($data["url"]);
		
		//Load likes information, if required
		if($load_likes){
			$info->set_number_likes(components()->likes->count($info->get_id(), Likes::LIKE_GROUP));
		}

		return $info;

	}

	/**
	 * Turn a database group entry into GroupSettings object
	 * 
	 * @param array $data Database entry
	 * @return GroupSettings The settings of the group
	 */
	private function dbToGroupSettings(array $data) : GroupSettings {

		//Parse advanced settings about the group
		$info = new GroupSettings();
		$this->dbToAdvancedGroupInfo($data, $info);

		return $info;

	}

	/**
	 * Turn a GroupSettings object into a database entry
	 * 
	 * @param GroupSettings $settings The object to convert
	 * @return array Generated database entry
	 */
	private function GroupSettingsToDB(GroupSettings $settings) : array {
		$data = array();

		if($settings->has_name())
			$data["name"] = $settings->get_name();

		if($settings->has_logo())
			$data["path_logo"] = $settings->get_logo();

		if($settings->has_visibility())
			$data["visibility"] = $settings->get_visibility();
		
		if($settings->has_registration_level())
			$data["registration_level"] = $settings->get_registration_level();
		
		if($settings->has_posts_level())
			$data["posts_level"] = $settings->get_posts_level();

		$data["virtual_directory"] = 
			$settings->has_virtual_directory() ? $settings->get_virtual_directory() : "";

		$data["description"] = 
			$settings->has_description() ? $settings->get_description() : "";

		$data["url"] = 
			$settings->has_url() ? $settings->get_url() : "";

		return $data;
	}

	/**
	 * Turn multiple database entries into GroupMember entries
	 * 
	 * @param array $entries The entries to process
	 * @return array Generated GroupMember objects
	 */
	private function multipleDBToGroupMember(array $entries) : array {
		foreach($entries as $num => $entry)
			$entries[$num] = $this->dbToGroupMember($entry);
		
		return $entries;
	}

	/**
	 * Turn a database entry into a GroupMember entry
	 * 
	 * @param array $entry The database entry to convert
	 * @return GroupMember Generated entry
	 */
	private function dbToGroupMember(array $entry) : GroupMember {

		$member = new GroupMember();

		$member->set_id($entry["id"]);
		$member->set_group_id($entry["groups_id"]);
		$member->set_userID($entry["user_id"]);
		$member->set_time_sent($entry["time_create"]);
		$member->set_level($entry["level"]);
		$member->set_following($entry["following"] == 1);

		return $member;

	}
}

//Register component
Components::register("groups", new GroupsComponent());