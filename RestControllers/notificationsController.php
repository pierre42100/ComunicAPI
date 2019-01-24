<?php
/**
 * Notifications REST controller
 * 
 * @author Pierre HUBERT
 */

class notificationsController {


	/**
	 * Count the number of unread notifications of a user
	 * 
	 * @url POST notifications/count_unread
	 */
	public function count_unread(){

		user_login_required();

		//Get the number of unread notifications
		$number = components()->notifications->count_unread(userID);

		//Return it
		return array(
			"number" => $number
		);

	}

	/**
	 * Count all the new kinds of data
	 * 
	 * - notifications
	 * - unread conversations
	 * 
	 * @url POST notifications/count_all_news
	 */
	public function count_all_news(){

		user_login_required();

		//Get and return the data
		$data = array(
			"notifications" => components()->notifications->count_unread(userID),
			"conversations" => components()->conversations->number_user_unread(userID)
		);

		//Include friendship requests if required
		if(isset($_POST["friends_request"]))
			if(postBool("friends_request"))
				$data["friends_request"] = components()->friends->count_requests(userID);

		
		//Include pending calls if required
		if(isset($_POST["include_calls"]))
			if(postBool("include_calls"))
				$data["calls"] = components()->calls->countPendingResponsesForUser(userID);

		return $data;
	}


	/**
	 * Get the list of unread notifications of a user
	 * 
	 * @url POST notifications/get_list_unread
	 */
	public function get_list_unread(){

		user_login_required();

		//Get the list of unread notifications
		$list = components()->notifications->list_unread(userID);

		//Process the list of notifications
		foreach($list as $num => $data){
			$list[$num] = $this->notifToArray($data);
		}

		//Return the list of notifications
		return $list;

	}

	/**
	 * Mark a notification as seen
	 * 
	 * @url POST notifications/mark_seen
	 */
	public function mark_seen(){

		user_login_required();

		//Get notification ID
		$notifID = $this->getPostNotifID("notifID");

		//Check the kind of deletion to do
		$delete_similar = false;
		if(isset($_POST['delete_similar']))
			$delete_similar = ($_POST['delete_similar'] === "true");

		//Get informations about the notification
		$infos_notif = components()->notifications->get_single($notifID);

		//Check for error
		if(!$infos_notif->has_id())
			Rest_fatal_error(500, "An error occured while trying to process the notification !");
		
		//Perform the required kind of deletion required
		$notification = new Notification();

		if(!$delete_similar){
			$notification->set_id($notifID);
		}

		else {
			$notification->set_on_elem_type($infos_notif->get_on_elem_type());
			$notification->set_on_elem_id($infos_notif->get_on_elem_id());
			$notification->set_dest_user_id($infos_notif->get_dest_user_id());
		}

		//Delete the notification
		if(!components()->notifications->delete($notification))
			Rest_fatal_error(500, "Could not delete the notification !");
		
		//Success
		return array("success" => "The notification was deleted.");

	}

	/**
	 * Delete all the notifications of a user
	 * 
	 * @url POST notifications/delete_all
	 */
	public function delete_all(){

		user_login_required();

		//Try to delete the list of notifications of the user
		if(!components()->notifications->delete_all_user(userID))
			Rest_fatal_error(500, "Could not delete user's notifications !");

		//Success
		return array("success" => "The notifications have been deleted !");
	}

	/**
	 * Get a valid notification ID in a POST request
	 * 
	 * @param string $name The name of the post field containing the notification ID
	 * @return int The ID of the notification
	 */
	private function getPostNotifID(string $name) : int {

		user_login_required();

		//Check the POST request
		if(!isset($_POST[$name]))
			Rest_fatal_error(400, "Please specify the ID of the notification in '".$name."' !");
		
		$notifID = (int) $_POST[$name];

		//Check if the notification exists and if the user is allowed to use it
		$notif = new Notification();
		$notif->set_dest_user_id(userID);
		$notif->set_id($notifID);
		if(!components()->notifications->similar_exists($notif))
			Rest_fatal_error(404, "The specified notification was not found !");

		return $notifID;
	}

	/**
	 * Turn a notification entry into a standard API notification array
	 * 
	 * Warning !!! The notification must be fully initialized
	 * 
	 * @param Notification $notif The notification to process
	 * @return array The generated array
	 */
	private function notifToArray(Notification $notif) : array {

		$array = array();

		//Parse the notification
		$array['id'] = $notif->get_id();
		$array['time_create'] = $notif->get_time_create();
		$array['seen'] = $notif->is_seen();
		$array['from_user_id'] = $notif->get_from_user_id();
		$array['dest_user_id'] = $notif->get_dest_user_id();
		$array['on_elem_id'] = $notif->get_on_elem_id();
		$array['on_elem_type'] = $notif->get_on_elem_type();
		$array['type'] = $notif->get_type();
		$array['event_visibility'] = $notif->get_event_visibility();
		$array['from_container_id'] = $notif->get_from_container_id();
		$array['from_container_type'] = $notif->get_from_container_type();
		

		return $array;
	}
}