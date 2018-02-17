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
	 * Push a new notification
	 * 
	 * @param Notification $notification The notification to push
	 * @return bool TRUE if the notification was pushed / FALSE else
	 */
	public function push(Notification $notification) : bool {

		//Determine the visibility level of the notification
		if($notification->get_on_elem_type() == Notification::POST){

			//Fetch post informations
			$infos_post = components()->posts->get_single($notification->get_on_elem_id());

			//Update post informations
			$notification->set_from_container_type(Notification::USER_PAGE);
			$notification->set_from_container_id($infos_post['user_page_id']);

			//Check if the notification is private or not
			if($infos_post['visibility_level'] == Posts::VISIBILITY_USER){
				
				//Push the notification only to the user, and only if it is not him
				if($notification->get_from_user_id() == $infos_post['userID'])
					return false; //Nothing to be done

				//Set the target user
				$notification->set_dest_user_id($infos_post['user_page_id']);

				//Push the notification
				return $notification->push_private($notification);
			}
			else {

				//Get the list of friends of the user
				$friendslist = components()->friends->getList($infos_post['user_page_id']);

				//Generate the list of target users
				$target_users = array();
				foreach($friendslist as $friend){

					//Check if the friend is following his friend
					if(!components()->friends->is_following($friend->getFriendID(), $notification->get_from_user_id())){
						continue;
					}

					//Check if target user and page owner are friends
					if(!components()->friends->are_friend($friend->getFriendID(), $notification->from_container_id()))
						continue;
					
					//Add the user to the list
					$target_users[] = $friend->getFriendID();

				}

				//The notification can be publicy published
				return $this->push_public($notification, $target_users);

			}

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

		//Process the list of users
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
		$conditions = array(

			"seen" => $notification->is_seen() ? 1 : 0,
			"dest_user_id" => $notification->get_dest_user_id(),
			"on_elem_id" => $notification->get_on_elem_id(),
			"on_elem_type" => $notification->get_on_elem_type(),
			"type" => $notification->get_type()

		);

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
		$values = $this->noticationToDB($notification);

	}

	/**
	 * Convert a notification object into database array
	 * 
	 * @param Notification $notification The notification to convert
	 * @return array The converted array
	 */
	private function noticationToDB(Notification $notification) : array {

		$data = array();

		

		return $data;
	}

}

//Register component
Components::register("notifications", new notificationComponent());