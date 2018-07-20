<?php

/**
 * User notification component
 * 
 * @author Pierre HUBERT
 */

class notificationComponent {

	/**
	 * Notifications table name
	 */
	const NOTIFICATIONS_TABLE = "comunic_notifications";

	/**
	 * Count the number of unread notifications of a user
	 * 
	 * @param int $userID Target user ID
	 * @return int The number of unread notifications
	 */
	public function count_unread(int $userID) : int {

		//Set the conditions of the request
		$conditions = "WHERE dest_user_id = ? AND seen = 0";
		$values = array($userID);

		//Compute and return result
		return CS::get()->db->count(self::NOTIFICATIONS_TABLE, $conditions, $values);

	}

	/**
	 * Get the list of unread notifications of a user
	 * 
	 * @param int $userID The target user ID
	 * @return array The list of notifications
	 */
	public function list_unread(int $userID) : array {

		//Perform a request on the server
		$conditions = "WHERE dest_user_id = ? AND seen = 0 ORDER BY id DESC";
		$values = array($userID);

		//Perform the request
		$results = CS::get()->db->select(self::NOTIFICATIONS_TABLE, $conditions, $values);

		//Process the list of results
		$list = array();
		foreach($results as $row)
			$list[] = $this->dbToNotification($row);
		
		//Return result
		return $list;
	}

	/**
	 * Get the informations about a single notification
	 * 
	 * @param int $notifID The target notification ID
	 * @return Notification The target notification
	 */
	public function get_single(int $notifID) : Notification {

		//Perform database request
		$conditions = "WHERE id = ?";
		$values = array($notifID);

		//Perform the request
		$result = CS::get()->db->select(self::NOTIFICATIONS_TABLE, $conditions, $values);

		//Check for error
		if(count($result) == 0)
			return new Notification(); //Return empty notification
		
		//Parse and return notification
		return $this->dbToNotification($result[0]);

	}

	/**
	 * Push a new notification
	 * 
	 * @param Notification $notification The notification to push
	 * @return bool TRUE if the notification was pushed / FALSE else
	 */
	public function push(Notification $notification) : bool {

		//Check if the time of creation of the notification has to be set
		if(!$notification->has_time_create())
			$notification->set_time_create(time());

		//Determine the visibility level of the notification
		if($notification->get_on_elem_type() == Notification::POST){

			//Fetch post informations
			$info_post = components()->posts->get_single($notification->get_on_elem_id());

			//Check for error
			if(!$info_post->isValid())
				return false;
				
			//Update post informations
			if($info_post->get_kind_page() == Posts::PAGE_KIND_USER){
				$notification->set_from_container_type(Notification::USER_PAGE);
				$notification->set_from_container_id($info_post->get_user_page_id());
			}
			else if($info_post->get_kind_page() == Posts::PAGE_KIND_GROUP){
				$notification->set_from_container_type(Notification::GROUP_PAGE);
				$notification->set_from_container_id($info_post->get_group_id());
			}
			else
				throw new Exception("Unsupported page kind: ".$info_post->get_kind_page());
			

			//Check if the notification is private or not
			//Private posts
			if($info_post->get_visibility_level() == Posts::VISIBILITY_USER){

				//Push the notification only to the user, and only if it is not him
				if($notification->get_from_user_id() == $info_post->get_user_page_id())
					return false; //Nothing to be done

				//Set the target user
				$notification->set_dest_user_id($info_post->get_user_page_id());

				//Push the notification
				return $this->push_private($notification);
			}

			//For the posts on user pages
			else if($notification->get_from_container_type() == Notification::USER_PAGE) {

				//Get the list of friends of the user
				$friendslist = components()->friends->getList($notification->get_from_user_id());

				//Generate the list of target users
				$target_users = array();
				foreach($friendslist as $friend){
						
					//Check if target user and page owner are friends
					if(!components()->friends->are_friend($friend->getFriendID(), $notification->get_from_container_id())
						AND $friend->getFriendID() != $notification->get_from_container_id())
						continue;
										
					//Check if the friend is following his friend
					if(!components()->friends->is_following($friend->getFriendID(), $notification->get_from_user_id())){
						continue;
					}
					
					//Add the user to the list
					$target_users[] = $friend->getFriendID();

				}

				//The notification can be publicy published
				return $this->push_public($notification, $target_users);

			}

			//For the posts on groups
			else if($notification->get_from_container_type() == Notification::GROUP_PAGE){
				
				//Get the list of the members of the group that follows it
				$list = components()->groups->getListFollowers($notification->get_from_container_id());
				

				//Process the list of followers
				$target_users = array();
				foreach($list as $userID){

					//If the current follower is the user creating the notification
					if($userID == $notification->get_from_user_id())
						continue;
					
					$target_users[] = $userID;
				}

				//Push the notification
				return $this->push_public($notification, $target_users);
			}

			//Unimplemented scenario
			else {
				throw new Exception("Notification scenarios not implemented!");
			}

		}

		//Handles friendship request notifications
		else if($notification->get_on_elem_type() == Notification::FRIENDSHIP_REQUEST){

			//Complete the notification
			$notification->set_from_container_id(0);
			$notification->set_from_container_type("");
			
			//Push the notification in private way
			return $this->push_private($notification);

		}
		
		//Unsupported element
		else {
			throw new Exception("The kind of notification ".$notification->get_on_elem_type()." is not currently supported !");
		}
		
	}

	/**
	 * Push a notification to several users
	 * 
	 * @param Notification $notification The notification to push
	 * @param array $usersID The list of target users
	 * @return bool FALSE for a failure
	 */
	private function push_public(Notification $notification, array $usersID) : bool {

		//Mark the notification as public
		$notification->set_event_visibility(Notification::EVENT_PUBLIC);

		//Process the list of userso
		foreach($usersID as $current_user){

			//Set the current user id for the notification
			$notification->set_dest_user_id($current_user);
			
			//Check if a similar notification already exists or not
			if($this->similar_exists($notification))
				continue; //A similar notification already exists
			
			//Create the notification
			$this->create($notification);
		}

		

		return true;
	}


	/**
	 * Push a private notification
	 * 
	 * Warning ! The target user for the notification must be already set !!!
	 * 
	 * @param Notification $notification The notification to push
	 * @return bool TRUE if the notification was created / FALSE else
	 */
	private function push_private(Notification $notification) : bool {

		//Mark the notification as private
		$notification->set_event_visibility(Notification::EVENT_PRIVATE);

		//Check if a similar notification already exists or not
		if($this->similar_exists($notification))
			return false; //A similar notification already exists

		//Create the notification
		return $this->create($notification);

	}


	/**
	 * Check if a notification similar to the given one exists or not
	 * 
	 * @param Notification $notification The notification to compare
	 * @return bool TRUE if a similar notification exists / FALSE else
	 */
	public function similar_exists(Notification $notification) : bool {
		
		//Prepare the request
		$tableName = self::NOTIFICATIONS_TABLE;
		$conditions = $this->noticationToDB($notification, false);

		//Make conditions
		$process_conditions = CS::get()->db->splitConditionsArray($conditions);
		$conditions = "WHERE ".$process_conditions[0];
		$values = $process_conditions[1];
		
		//Return the result
		return CS::get()->db->count($tableName, $conditions, $values) > 0;

	}

	/**
	 * Create a notification
	 * 
	 * @param Notification $notification The notification to create
	 * @return bool TRUE for a success / FALSE else
	 */
	private function create(Notification $notification) : bool {

		//Generate notification datas
		$values = $this->noticationToDB($notification, TRUE);

		//Save the notification in the database
		return CS::get()->db->addLine(self::NOTIFICATIONS_TABLE, $values);
	}

	/**
	 * Delete notifications
	 * 
	 * @param Notification $notification The notification to delete
	 * @return bool TRUE for a success / FALSE else
	 */
	public function delete(Notification $notification) : bool {

		//If the notification has an ID we delete a notification by its ID
		if($notification->has_id()){
			//Delete a notification by its ID
			$conditions = "id = ?";
			$values = array($notification->get_id());
		}
		
		//Delete the notification
		else{

			//Delete a specific notification
			$data = $this->noticationToDB($notification, FALSE);
			$array = CS::get()->db->splitConditionsArray($data);
			$conditions = $array[0];
			$values = $array[1];

		}


		//Delete the notification
		return CS::get()->db->deleteEntry(self::NOTIFICATIONS_TABLE, $conditions, $values);
	}

	/**
	 * Delete all the notifications of a user
	 * 
	 * @param int $userID The ID of the target user
	 * @return bool TRUE for a success / FALSE for a failure
	 */
	public function delete_all_user(int $userID) : bool {
		
		//Perform the request
		$notif = new Notification();
		$notif->set_dest_user_id($userID);
		return $this->delete($notif);

	}


	/**
	 * Delete all the notifications related with a user
	 * 
	 * @param int $userID The ID of the target user
	 * @return bool TRUE for a success / FALSE else
	 */
	public function deleteAllRelatedWithUser(int $userID) : bool {

		//Delete all the notifications targeting the user
		if(!$this->delete_all_user($userID))
			return FALSE;
		
		//Delete all the notifications created by the user
		$notif = new Notification();
		$notif->set_from_user_id($userID);
		return $this->delete($notif);

	}

	/**
	 * Convert a notification object into database array
	 * 
	 * @param Notification $notification The notification to convert
	 * @param bool $full_entry  TRUE to process the entire notification, else only
	 * the main informations will be processed
	 * @return array The converted array
	 */
	private function noticationToDB(Notification $notification, bool $full_entry = TRUE) : array {

		$data = array();
		
		if($notification->has_id())
			$data['id'] = $notification->get_id();
		if($notification->has_seen_state())
			$data['seen'] = $notification->is_seen() ? 1 : 0;
		if($notification->has_from_user_id())
			$data['from_user_id'] = $notification->get_from_user_id();
		if($notification->has_dest_user_id())
			$data['dest_user_id'] = $notification->get_dest_user_id();
		if($notification->has_type())
			$data['type'] = $notification->get_type();
		if($notification->has_on_elem_id())
			$data['on_elem_id'] = $notification->get_on_elem_id();
		if($notification->has_on_elem_type())
			$data['on_elem_type'] = $notification->get_on_elem_type();

		//Specify complementary fields only if required
		if($full_entry){
			$data['from_container_id'] = $notification->get_from_container_id();
			$data['from_container_type'] = $notification->get_from_container_type();
			$data['time_create'] = $notification->get_time_create();
			$data['visibility'] = $notification->get_event_visibility();
		}

		return $data;
	}

	/**
	 * Convert a notification database entry into a Notification object
	 * 
	 * @param array $row The database entry to process
	 * @return Notification The generated notification
	 */
	private function dbToNotification(array $row) : Notification {

		$notif = new Notification();

		//Parse database entry
		$notif->set_id($row['id']);
		$notif->set_seen($row['seen'] == 1);
		$notif->set_time_create($row['time_create']);
		$notif->set_from_user_id($row['from_user_id']);
		$notif->set_dest_user_id($row['dest_user_id']);
		$notif->set_on_elem_id($row['on_elem_id']);
		$notif->set_on_elem_type($row['on_elem_type']);
		$notif->set_type($row['type']);
		$notif->set_event_visibility($row['visibility']);
		$notif->set_from_container_id($row['from_container_id']);
		$notif->set_from_container_type($row['from_container_type']);

		return $notif;

	}

}

//Register component
Components::register("notifications", new notificationComponent());