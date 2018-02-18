<?php
/**
 * Notifications REST controller
 * 
 * @author Pierre HUBERT
 */

class notificationsController {


	/**
	 * Get the number of unread notifications of a user
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

}