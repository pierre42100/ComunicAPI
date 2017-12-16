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
		$usersList = numbers_list_to_array($_POST['users']);

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
				$conversationMembers = numbers_list_to_array($_POST['members']);

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

		//Check for images
		if($image){
			//Get image informations
			$arrayImage = explode(";", $image);
			if(count($arrayImage) != 2)
				Rest_fatal_error(400, "Invalid image informations !");
			
			$image = str_replace("base64,", "", $arrayImage[1]);

			//Try to base64_decode image
			$decoded_image = base64_decode($image, true);
			
			//Check for errors
			if(!$decoded_image)
				Rest_fatal_error(500, "Couldn't extract image !");
			
			//Get target folder
			$targetFolder = prepareFileCreation(userID, "conversations");

			//Generate target file name
			$targetFileName = $targetFolder.generateNewFileName(path_user_data($targetFolder, true), "png");
			$relativeFileName = path_user_data($targetFileName, true);

			//Try to resize image
			if(!reduce_image("string", $relativeFileName, 1200, 1200, "image/png", $decoded_image)){
				//Returns error
				Rest_fatal_error(500, "Couldn't resize image !");
			}

			//Save image URI
			$image = $targetFileName;
		}

		//Insert the new message
		if(!CS::get()->components->conversations->sendMessage(userID, $conversationID, $message, $image))
			Rest_fatal_error(500, "Couldn't send the message !");
		
		//Success
		return array("success" => "Conversation message with successfully added !");
	}

	/**
	 * Refresh conversations
	 *
	 * @url POST /conversations/refresh
	 */
	public function refreshConversations(){
		user_login_required();

		//Prepare return
		$conversationsMessages = array();

		//Check if we have to give the latest messages of some conversations
		if(isset($_POST['newConversations'])){
			//Get conversations ID
			$newConversations = numbers_list_to_array($_POST['newConversations']);

			foreach($newConversations as $conversationID){
				//First, check the user belongs to the conversation
				if(!CS::get()->components->conversations->userBelongsTo(userID, $conversationID))
					Rest_fatal_error(401, "Specified user doesn't belongs to the conversation number ".$conversationID." !");
				
				//Then we can get the ten last messages of the conversation system
				$conversationsMessages["conversation-".$conversationID] = CS::get()->components->conversations->getLastMessages($conversationID, 10);

				//Specify that user has seen last messages
				CS::get()->components->conversations->markUserAsRead(userID, $conversationID);
			}
		}

		//Check if we have to refresh some conversations
		if(isset($_POST['toRefresh'])){

			//Try to decode informations about the conversations
			$toRefresh = json_decode($_POST['toRefresh'], true);
			if($toRefresh === false)
				Rest_fatal_error(400, "Couldn't get refresh conversations informations !");
			
			//Process each conversation
			foreach($toRefresh as $conversationID=>$informations){

				//Get conversation ID
				$conversationID = toInt(str_replace("conversation-", "", $conversationID));

				//Check if the conversation is not a new conversation too
				if(isset($conversationsMessages["conversation-".$conversationID]))
					Rest_fatal_error(401, "Conversation marked as new can't be refreshed !");

				//Check if conversation number is valid
				if($conversationID < 1)
					Rest_fatal_error(401, "An error occured while trying to extract given conversation ID to refresh: ".$conversationID);
				
				//Check if informations where given about the limit of the informations to get
				if(!isset($informations["last_message_id"]))
					Rest_fatal_error(401, "Conversation ".$conversationID." couldn't be refreshed: not enough informations !");
				$last_message_id = toInt($informations["last_message_id"]);

				//Check if the user belongs to the conversation
				if(!CS::get()->components->conversations->userBelongsTo(userID, $conversationID))
					Rest_fatal_error(401, "Specified user doesn't belongs to the conversation number ".$conversationID." !");
				
				//Then we can get informations about the conversation
				$conversationsMessages["conversation-".$conversationID] = CS::get()->components->conversations->getNewMessages($conversationID, $last_message_id);

				//Specify that user has seen last messages
				CS::get()->components->conversations->markUserAsRead(userID, $conversationID);
			}
		}

		//Return result
		return $conversationsMessages;
	}

	/**
	 * Refresh the messages of a specific conversation
	 *
	 * @url POST /conversations/refresh_single
	 */
	public function refresh_single(){
		user_login_required();

		//Get the ID of the conversation to refresh
		if(!isset($_POST['conversationID']))
			Rest_fatal_error(400, "Please specify a conversation ID !");
		
		//Get the last message ID downloaded by the client
		if(!isset($_POST['last_message_id']))
			Rest_fatal_error(400, "Please specify the ID of the last message you've got!");

		$conversationID = toInt($_POST['conversationID']);
		$last_message_id = toInt($_POST['last_message_id']);

		//Check if the current user can access the conversation
		if(!CS::get()->components->conversations->userBelongsTo(userID, $conversationID))
			Rest_fatal_error(401, "Specified user doesn't belongs to the conversation number ".$conversationID." !");

		//Check if user has already some of the messages of the conversations, or
		//If we have to return the list of the last ten messages
		if($last_message_id == 0){
			$messages = 
				CS::get()->components->conversations->getLastMessages($conversationID, 10);
		}
		else {
			$messages = 
				CS::get()->components->conversations->getNewMessages($conversationID, $last_message_id);
		}

		//Return the messges
		return $messages;
		
	}
}