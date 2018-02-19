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