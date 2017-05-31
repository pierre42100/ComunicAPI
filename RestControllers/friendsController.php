<?php
/**
 * Friends controller
 *
 * @author Pierre HUBERT
 */

class friendsController{

	/**
	 * Get friends list
	 *
	 * @url POST /friends/getList
	 */
	public function getFriendsList(){
		user_login_required(); //Login required

		//Try to get friends list
		$friendsList = CS::get()->components->friends->getList(userID);

		//Check for errors
		if($friendsList === false)
			Rest_fatal_error(500, "Couldn't get friends list !");
		
		//Return list
		return $friendsList;
	}

}