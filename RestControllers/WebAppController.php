<?php
/**
 * Web application controller
 * 
 * Methods specifically targetting the web application
 * 
 * @author Pierre HUBERT
 */

class WebAppController {

	// Kins of membership
	const MEMBERSHIP_FRIEND = "friend";
	const MEMBERSHIP_GROUP = "group";

	public function __construction() {
		user_login_required();
	}

	/**
	 * Get all the memberships of the user, sorted by last activity order
	 * 
	 * @url POST /webApp/getMemberships
	 */
	public function getMemberships() {

		// Get the list of friends of the user
		$friends = components()->friends->getList(userID);

		// Get the list of groups of the user
		$groups = components()->groups->getListUser(userID);

		// Get last activities of groups
		$groups_activity = array();
		foreach($groups as $group)
			$groups_activity[components()->groups->getLastActivity($group)] = $group;
		krsort($groups_activity);
		$groups = array();
		foreach($groups_activity as $activity => $id)
			$groups[] = array("id" => $id, "activity" => $activity);

		$out = array();
		while(count($friends) != 0 || count($groups) != 0) {

			if(count($friends) == 0)
				$type = self::MEMBERSHIP_GROUP;
			
			else if(count($groups) == 0)
				$type = self::MEMBERSHIP_FRIEND;
			
			else if($friends[0]->getLastActivityTime() > $groups[0]["activity"])
				$type = self::MEMBERSHIP_FRIEND;
			
			else
				$type = self::MEMBERSHIP_GROUP;
			
			// In case of friend
			if($type == self::MEMBERSHIP_FRIEND){
				$out[] = array(
					"type" => $type,
					"friend" => friendsController::parseFriendAPI(array_shift($friends))
				);
			}

			// In case of group
			else {
				$info = array_shift($groups);
				$out[] = array(
					"type" => $type,
					"id" => (int)$info["id"],
					"last_activity" => $info["activity"]
				);
			}

		}


		return $out;
	}

}