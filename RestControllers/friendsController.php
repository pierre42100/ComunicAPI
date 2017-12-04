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

}