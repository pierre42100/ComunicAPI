<?php
/**
 * Conversations controller
 *
 * @author Pierre HUBERT
 */

class conversationsController{

	/**
	 * Get the conversations list
	 *
	 * @url POST /conversations/getList
	 */
	public function getConversationsList(){
		user_login_required();

		//Try to get the list
		$conversationsList = CS::get()->components->conversations->getList(userID);

		//Check for errors
		if($conversationsList === false)
			Rest_fatal_error(500, "Couldn't get conversations list !");
		
		//Return results
		return $conversationsList;
	}

	/**
	 * Create a new conversation
	 *
	 * @url POST /conversations/create
	 */
	public function createConversation(){
		user_login_required();

		//Check for parametres
		if(!check_post_parametres(array("name", "follow", "users")))
			Rest_fatal_error(501, "Please check parametres passed with the request !");
		
		//Extract parametres
		$conversationName = ($_POST["name"] == "false" ? false : $_POST['name']);
		$followConversation = ($_POST['follow'] == "true" ? true : false);
		$usersList = users_list_to_array($_POST['users']);

		//Add current user (if not present)
		if(!isset($usersList[userID]))
			$usersList[userID] = userID;

		//Check users
		if(count($usersList) < 1)
			Rest_fatal_error(501, "Please select at least one user !");
		
		//Try to create the conversation
		$conversationID = CS::get()->components->conversations->create(userID, $followConversation, $usersList, $conversationName);
		
		//Check for errors
		if($conversationID == 0)
			Rest_fatal_error(500, "Couldn't create the conversation !");

		//Success
		return array(
			"conversationID" => $conversationID,
			"success" => "The conversation was successfully created !"
		);
	}

}