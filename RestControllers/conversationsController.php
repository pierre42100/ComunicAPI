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
	public function getList(){
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
	 * Get informationsd about one conversation
	 *
	 * @url POST /conversations/getInfosOne
	 */
	public function getOneInformations(){
		user_login_required();

		//First, check the parametres
		if(!isset($_POST['conversationID']))
			Rest_fatal_error(501, "No conversation ID specified with the request");
		
		//Extract data
		$conversationID = toInt($_POST['conversationID']);
		
		//Try to get informations about the conversation
		$conversationsList = CS::get()->components->conversations->getList(userID, $conversationID);

		//Check for errors
		if($conversationsList === false)
			Rest_fatal_error(500, "An internal error occured");

		//Check if a conversation was found
		if(count($conversationsList) < 1)
			Rest_fatal_error(401, "Users doesn't belong to the specified conversation,".
			"or the conversation doesn't exists !");

		//Return conversation informations
		return $conversationsList[0];
	}

	/**
	 * Create a new conversation
	 *
	 * @url POST /conversations/create
	 */
	public function create(){
		user_login_required();

		//Check for parametres
		if(!check_post_parametres(array("name", "follow", "users")))
			Rest_fatal_error(400, "Please check parametres passed with the request !");
		
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

	/**
	 * Update a conversation settings
	 *
	 * @url POST /conversations/updateSettings
	 */
	public function updateSettings(){
		user_login_required();

		//Check conversation ID was specified
		if(!isset($_POST["conversationID"]))
			Rest_fatal_error(400, "Please specify a conversation ID !");
		$conversationID = toInt($_POST["conversationID"]);

		//Check if the user belongs to the conversation
		if(!CS::get()->components->conversations->userBelongsTo(userID, $conversationID))
			Rest_fatal_error("401", "Specified user doesn't belongs to the conversation !");

		//Check if user want to update its follow state
		if(isset($_POST['following'])){
			$follow = $_POST["following"] === "true" ? true : false;

			//Try to update follow state
		 	if(!CS::get()->components->conversations->changeFollowState(userID, $conversationID, $follow))
		 		Rest_fatal_error(500, "Couldn't update user follow state !");
		}
		
		//Check if user asked to change moderation settings
		if(isset($_POST['members']) OR isset($_POST['name'])){

			//Check if user is allowed to change such settings
			if(!CS::get()->components->conversations->userIsModerator(userID, $conversationID))
				Rest_fatal_error(401, "The user isn't a moderator, he can't updates such settings !");
			
			//Update conversation name (if required)
			if(isset($_POST["name"])){
				$conversationName = $_POST['name'] == "false" ? "" : $_POST['name'];

				//Update conversation name
				if(!CS::get()->components->conversations->changeName($conversationID, $conversationName))
					Rest_fatal_error(500, "Couldn't update conversation name !");
			}

			//Update conversation users (if required)
			if(isset($_POST["members"])){
				//Get user list
				$conversationMembers = users_list_to_array($_POST['members']);

				//Make sure current user is in the list
				$conversationMembers[userID] = userID;

				//Try to update conversation members
				if(!CS::get()->components->conversations->updateMembers($conversationID, $conversationMembers))
					Rest_fatal_error(500, "Couldn't update conversation members list !");
			}

		}
			
		//Success
		return array("success" => "Conversation informations were successfully updated !");
	}

	/**
	 * Find (create ?) and return private conversation ID
	 *
	 * @url POST /conversations/getPrivate
	 */
	public function findPrivate(){

		user_login_required();

		//Check for parametres
		if(!isset($_POST['otherUser']))
			Rest_fatal_error(400, "Please check your parametres !");
		
		//Extract parametres
		$otherUser = toInt($_POST['otherUser']);
		if(isset($_POST["allowCreate"]))
			$allowCreate = $_POST["allowCreate"] == "true" ? true : false;
		else
			$allowCreate = false;
		
		//Check the user exists
		if(!CS::get()->components->user->exists($otherUser))
			Rest_fatal_error(400, "Specified user does not exist !");

		//Search the database
		$results = CS::get()->components->conversations->findPrivate(userID, $otherUser);
		
		//Count results number
		if(count($results) === 0) {

			//We check if we are not allowed to create a conversation
			if(!$allowCreate)
				Rest_fatal_error(404, "Not any private conversation were found. The server wasn't allowed to create a new one...");
			
			//Now we can try to create the conversation
			$ID_owner = userID;
			$follow = false; //Not following by default
			$conversationMembers = array(userID, $otherUser);

			//Try to create the conversation
			$conversationID = CS::get()->components->conversations->create($ID_owner, $follow, $conversationMembers);
			
			//Check for errors
			if($conversationID == 0)
				Rest_fatal_error(500, "Couldn't create the conversation !");
			
			//Save result
			$results = array($conversationID);
		}

		//Success
		return array("conversationsID" => $results); 
	}

	/**
	 * Send a new message
	 *
	 * @url POST /conversations/sendMessage
	 */
	public function sendMessage(){
		user_login_required();

		//First, check a conversation ID was specified
		if(!isset($_POST["conversationID"]))
			Rest_fatal_errror(400, "Please speicify a conversation ID !");

		//Extract conversation ID
		$conversationID = toInt($_POST["conversationID"]);

		//Check if the user belongs to the conversation
		if(!CS::get()->components->conversations->userBelongsTo(userID, $conversationID))
			Rest_fatal_error(401, "Specified user doesn't belongs to the conversation !");

		//Check if informations were specified about the new message or not
		if(!isset($_POST['message']) AND !isset($_POST['image']))
			Rest_fatal_error(401, "Nothing to be sent with the new message !");
		
		//Else extract informations
		$message = (isset($_POST['message']) ? $_POST['message'] : "");
		$image = (isset($_POST['image']) ? $_POST['image'] : false);

		//Check message validity
		if(!check_string_before_insert($message) && !$image)
			Rest_fatal_error(401, "Invalid message sending request !");

		//Process images NOT IMPLEMENTED YET

		//Insert the new message
		if(!CS::get()->components->conversations->sendMessage(userID, $conversationID, $message))
			Rest_fatal_error(500, "Couldn't send the message !");
		
		Rest_fatal_error("200", "All right now");
	}
}