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
		
		//Update the last activity of the user
		CS::get()->components->user->updateLastActivity(userID);
		
		//Return list
		return $friendsList;
	}

	/**
	 * Send a friendship request
	 * 
	 * @url POST /friends/sendRequest
	 */
	public function sendRequest(){
		user_login_required(); //Login required

		//Check parametres
		if(!isset($_POST["friendID"]))
			Rest_fatal_error(400, "Please specify a user ID !");
		
		//Extract informations and process request
		$friendID = toInt($_POST['friendID']);

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
		
		//Success
		return array("success" => "The friendship request has been created !");

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

		//Check if the operation is a result
		if(!$result)
			Rest_fatal_error(500, "Couldn't remove user from the friendlist for an unexcepted reason !");
		else
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

		//Check if the a friendID has been specified
		if(!isset($_POST['friendID']))
			Rest_fatal_error(400, "Please specify a friend ID !");

		//Get it
		$friendID = toInt($_POST['friendID']);

		//Prepare the response
		$response = array(
			"are_friend" => false,
			"sent_request" => false,
			"received_request" => false,
			"following" => false,
			"can_post_texts" => false,
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

			//Check if the user can post text on his friend page
			if(CS::get()->components->friends->can_post_text(userID, $friendID))
				$response['can_post_texts'] = true;

		}

		//Return the response
		return $response;
	}
}