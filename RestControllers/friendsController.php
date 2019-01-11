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

		//Check if the user want all the informations about its friends or not
		$all_infos = false;
		if(isset($_POST['complete']))
			$all_infos = $_POST['complete'] === "true";

		//Try to get friends list
		$friendsList = CS::get()->components->friends->getList(userID);

		//Check for errors
		if($friendsList === false)
			Rest_fatal_error(500, "Couldn't get friends list !");
		
		//Process the list
		$api_list = array();
		foreach($friendsList as $friend){

			//Parse friend informations
			$api_list[] = $this->parseFriendAPI($friend, $all_infos);

		}

		//Update the last activity of the user
		update_last_user_activity_if_allowed();

		//Return list
		return $api_list;
	}

	/**
	 * Get the list of friends of a specific user
	 * 
	 * @url POST /friends/get_user_list
	 */
	public function get_user_list(){

		//Get the ID of the target user
		$userID = getPostUserID("userID");

		//Check whether the friends list of the user is public or not
		if(!components()->user->userAllowed(userID, $userID))
			Rest_fatal_error(401, "You are not allowed to access these informations !");
		
		//Check if the friendlist of the user is public or not
		if(!components()->user->isFriendsListPublic($userID))
			Rest_fatal_error(401, "The friends list of the user is not public !");
		
		//Get the list of friend of the user
		$friends = CS::get()->components->friends->getList($userID);

		//Process and return it
		$IDs = array();

		foreach($friends as $friend){
			
			//Return only accepted friendships
			if($friend->isAccepted())
				$IDs[] = $friend->getFriendID();

		}

		return $IDs;
	}

	/**
	 * Get friendship informations about a specific friend
	 * 
	 * @url POST /friends/get_single_infos
	 */
	public function get_single_infos(){

		user_login_required();

		//Get friendID
		$friendID = getPostUserID('friendID');

		//Get informations about the friendship
		$list = components()->friends->getList(userID, $friendID);

		//Check if the friend was found or not
		if(count($list) == 0)
			Rest_fatal_error(404, "Specified friend not found !");

		//Return informations about the friend
		return $this->parseFriendAPI($list[0], true);
	}

	/**
	 * Send a friendship request
	 * 
	 * @url POST /friends/sendRequest
	 */
	public function sendRequest(){
		user_login_required(); //Login required

		//Get target ID
		$friendID = getPostUserID('friendID');

		//Check if the current user is requesting himself as friend
		if($friendID == userID)
			Rest_fatal_error(401, "You can not become a friend to yourself!");

		//Check if the two persons are already friend
		if(CS::get()->components->friends->are_friend(userID, $friendID))
			Rest_fatal_error(401, "The two personns are already friend !");
		
		//Check if there is already a pending request
		//Check if the current user has sent a request to the other user
		if(CS::get()->components->friends->sent_request(userID, $friendID))
			Rest_fatal_error(401, "You have already sent a friendship request to this personn !");
	
		//Check if the current user has received a friendship request
		if(CS::get()->components->friends->sent_request($friendID, userID))
			Rest_fatal_error(401, "You have already received a friendship request from this personn !");
		
		//We can now create the request
		if(!CS::get()->components->friends->send_request(userID, $friendID))
			Rest_fatal_error(500, "Couldn't create friendship request !");
		
		//Create notification
		create_friendship_notification(userID, $friendID, Notification::SENT_FRIEND_REQUEST);

		//Success
		return array("success" => "The friendship request has been created !");

	}

	/**
	 * Remove a previously sent frienship request
	 * 
	 * @url POST /friends/removeRequest
	 */
	public function removeRequest(){
		user_login_required(); //Login required

		//Get friendID
		$friendID = getPostUserID('friendID');

		//Check if the current user has sent a request to the other user
		if(!CS::get()->components->friends->sent_request(userID, $friendID))
			Rest_fatal_error(401, "You didn't send a friendship request to this user !");
		
		//Try to remove the friendship request
		if(!CS::get()->components->friends->remove_request(userID, $friendID))
			Rest_fatal_error(500, "An error occured while trying to remove the friendship request !");
		
		//Delete all related notifications
		delete_notifications_friendship_request(userID, $friendID);

		//This is a success
		return array("success" => "The friendship request has been removed!");
	}

	/**
	 * Respond to a friendship request
	 *
	 * @url POST /friends/respondRequest
	 */
	public function respondRequest(){
		user_login_required(); //Login required

		//Check parametres
		if(!isset($_POST["friendID"]) OR !isset($_POST['accept']))
			Rest_fatal_error(400, "Please check your parametres !");
		
		//Extract informations and process request
		$friendID = toInt($_POST['friendID']);
		$acceptRequest = $_POST['accept'] == "true";

		//Try to perform request
		$result = CS::get()->components->friends->respondRequest(userID, $friendID, $acceptRequest);

		//Return result
		if($result != true)
			Rest_fatal_error(500, "Couldn't respond to friendship request !");
		
		//Send notification
		create_friendship_notification(userID, $friendID, 
			$acceptRequest ? Notification::ACCEPTED_FRIEND_REQUEST : Notification::REJECTED_FRIEND_REQUEST
		);
		
		//Else it is a success
		return array("success" => "A response was given to friendship request !");
	}

	/**
	 * Delete a friend from the list
	 *
	 * @url POST /friends/remove
	 */
	public function delete(){
		user_login_required(); //Login required

		//Check input parametres
		if(!isset($_POST['friendID']))
			Rest_fatal_error(400, "Please specify the ID of the friend to delete !");
			
		//Delete the friend from the list
		$friendID = toInt($_POST['friendID']);
		$result = CS::get()->components->friends->remove(userID, $friendID);

		//Check if the operation is a success
		if(!$result)
			Rest_fatal_error(500, "Couldn't remove user from the friendlist for an unexcepted reason !");
		
		//Delete any related notification
		delete_notifications_friendship_request(userID, $friendID);

		//Success
		return array("success" => "The friend was removed from the list !");
	}

	/**
	 * Get the status of a friendship
	 * 
	 * Check if the users are friends, or this there is a pending request...
	 * 
	 * @url POST /friends/getStatus
	 */
	public function getStatus(){

		user_login_required(); //Login required

		//Get friendID
		$friendID = getPostUserID('friendID');

		//Prepare the response
		$response = array(
			"are_friend" => false,
			"sent_request" => false,
			"received_request" => false,
			"following" => false,
		);

		//Check if the two personns are friend
		$response['are_friend'] = 
			CS::get()->components->friends->are_friend(userID, $friendID);

		//Perform next check only if the personns are not already friend
		if(!$response['are_friend']){
			
			//Check if the current user has sent a request to the other user
			if(CS::get()->components->friends->sent_request(userID, $friendID))
				$response["sent_request"] = true;
			
			//Check if the current user has received a friendship request
			if(CS::get()->components->friends->sent_request($friendID, userID))
				$response["received_request"] = true;
		}
		else {

			//Perform the check specific to the real friend
			//Check if the user is following his friend or not
			if(CS::get()->components->friends->is_following(userID, $friendID))
				$response['following'] = true;

		}

		//Return the response
		return $response;
	}

	/**
	 * Update the following status of a friendship
	 * 
	 * @url POST /friends/setFollowing
	 */
	public function update_following(){
		user_login_required(); //Login required

		//Check if the a friendID has been specified
		$friendID = getPostUserID('friendID');

		//Check if a follow status has been specified
		if(!isset($_POST['follow']))
			Rest_fatal_error(400, "Please specify a follow status!");
		
		$following = $_POST['follow'] === "true";

		//Check if the two personns are friend
		if(!CS::get()->components->friends->are_friend(userID, $friendID))
			Rest_fatal_error(401, "You are not friend with this personn!");
		
		//Update following status
		if(!CS::get()->components->friends->set_following(userID, $friendID, $following))
			Rest_fatal_error(500, "Couldn't update friendship status!");
		
		//Success
		return array("success" => "Friendship status has been updated!");
	}

	/**
	 * Update the post text authorization status
	 * 
	 * @url POST /friends/set_can_post_texts
	 */
	public function update_can_post_texts(){

		user_login_required(); //Login required

		//Check if the a friendID has been specified
		$friendID = getPostUserID('friendID');

		//Check if a follow status has been specified
		if(!isset($_POST['allow']))
			Rest_fatal_error(400, "Please specify an authorization status!");
		
		$can_post_texts = $_POST['allow'] === "true";

		//Check if the two personns are friend
		if(!CS::get()->components->friends->are_friend(userID, $friendID))
			Rest_fatal_error(401, "You are not friend with this personn!");
		
		//Update status
		if(!components()->friends->set_can_post_texts(userID, $friendID, $can_post_texts))
			Rest_fatal_error(500, "Coudl not update friendship status !");

		//Success
		return array("success" => "Updated authorization status !");
	}

	/**
	 * Convert a friend object into an object readable by the api
	 * 
	 * @param Friend $friend The input friend
	 * @param bool $all_infos Specify if whether all the informations about the
	 * friendship should be returned or not
	 * @return array Informations about the friend readable by the api
	 */
	public static function parseFriendAPI(Friend $friend, bool $all_infos = FALSE) : array {

		//Parse informations about the friend
		$data = array(
			"ID_friend" => $friend->getFriendID(),
			"accepted" => $friend->isAccepted() ? 1 : 0,
			"time_last_activity" => $friend->getLastActivityTime()
		);

		//Check if all the informations about the friendship should be returned or not
		if($all_infos){
			
			//Following status
			$data["following"] = $friend->isFollowing() ? 1 : 0;

			//Can posts text on page
			$data["canPostTexts"] = $friend->canPostTexts();
		}

		return $data;
	}
}