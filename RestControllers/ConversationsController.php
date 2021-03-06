<?php
/**
 * Conversations controller
 *
 * @author Pierre HUBERT
 */

class ConversationsController {

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
		
		//Process the list of conversation
		foreach($conversationsList as $num => $conv)
			$conversationsList[$num] = self::ConvInfoToAPI($conv);
		
		//Return results
		return $conversationsList;
	}

	/**
	 * Get informationsd about one conversation
	 *
	 * @url POST /conversations/getInfosOne
	 * @url POST /conversations/getInfoOne
	 */
	public function getOneInformation(){
		user_login_required();

		//Get conversation ID
		$conversationID = $this->getSafePostConversationID("conversationID");
		
		//Try to get informations about the conversation
		$conversationsList = CS::get()->components->conversations->getList(userID, $conversationID);

		//Check for errors
		if($conversationsList === false)
			Rest_fatal_error(500, "An internal error occured");

		//Check if a conversation was found
		if(count($conversationsList) < 1)
			Rest_fatal_error(401, "Users doesn't belong to the specified conversation,".
			" or the conversation doesn't exists !");

		//Return conversation informations
		return self::ConvInfoToAPI($conversationsList[0]);
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
		$conv = new ConversationInfo();
		$conv->set_id_owner(userID);
		$conv->set_name($_POST["name"] == "false" ? false : removeHTMLnodes($_POST['name']));
		$conv->set_following($_POST['follow'] == "true" ? true : false);

		//Process the list of users
		$membersList = numbers_list_to_array($_POST['users']);

		//Add current user (if not present)
		if(!isset($membersList[userID]))
			$membersList[userID] = userID;

		//Check users
		if(count($membersList) < 1)
			Rest_fatal_error(501, "Please select at least one user !");
		
		$conv->set_members($membersList);
		
		//Try to create the conversation
		$conversationID = CS::get()->components->conversations->create($conv);
		
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

		//Get conversationID
		$conversationID = $this->getSafePostConversationID("conversationID");

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
		
		//Extract parametres
		$otherUser = getPostUserID('otherUser');

		//Check if we are allowed to create a conversation or not
		if(isset($_POST["allowCreate"]))
			$allowCreate = $_POST["allowCreate"] == "true" ? true : false;
		else
			$allowCreate = false;

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
			$conv = new ConversationInfo();
			$conv->set_id_owner(userID);
			$conv->set_name(false);
			$conv->set_following(true);
			$conv->add_member(userID);
			$conv->add_member($otherUser);
			$conversationID = CS::get()->components->conversations->create($conv);
			
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
			Rest_fatal_errror(400, "Please specify a conversation ID !");

		//Extract conversation ID
		$conversationID = toInt($_POST["conversationID"]);

		//Check if the user belongs to the conversation
		if(!CS::get()->components->conversations->userBelongsTo(userID, $conversationID))
			Rest_fatal_error(401, "Specified user doesn't belongs to the conversation !");

		//Check if information were specified about the new message or not
		if(!isset($_POST['message']))
			Rest_fatal_error(401, "New conversation messages must contain a message (can be empty if there is an image) !");
		
		//Else extract informations
		$content = (string) (isset($_POST['message']) ? $_POST['message'] : "");

		//Check for image
		$image = "";
		if(check_post_file("image")){

			//Save image and retrieve its URI
			$image = save_post_image("image", userID, "conversations", 1200, 1200);

		}

		//Check message validity
		if(!check_string_before_insert($content) && $image == "")
			Rest_fatal_error(401, "Invalid message creation request !");

		//Insert the new message
		$newMessage = new NewConversationMessage();
		$newMessage->set_userID(userID);
		$newMessage->set_conversationID($conversationID);
		$newMessage->set_message($content);
		$newMessage->set_image_path($image);
		if(!CS::get()->components->conversations->sendMessage($newMessage))
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

		//Process results
		foreach($conversationsMessages as $name=>$values){

			foreach($conversationsMessages[$name] as $num => $message)
				$conversationsMessages[$name][$num] = ConversationsController::ConvMessageToAPI($message);

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
		$conversationID = $this->getSafePostConversationID("conversationID");
		
		//Get the last message ID downloaded by the client
		if(!isset($_POST['last_message_id']))
			Rest_fatal_error(400, "Please specify the ID of the last message you've got!");

		$last_message_id = toInt($_POST['last_message_id']);

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

		//Specify that user has seen last messages
		CS::get()->components->conversations->markUserAsRead(userID, $conversationID);

		//Process the list of messages
		foreach($messages as $num => $val)
			$messages[$num] = ConversationsController::ConvMessageToAPI($val);

		//Return the messges
		return $messages;
		
	}

	/**
	 * Get older messages of a conversation
	 * 
	 * @url POST /conversations/get_older_messages
	 */
	public function getOlderMessages(){

		user_login_required();

		//Get the ID of the conversation to refresh
		$conversationID = $this->getSafePostConversationID("conversationID");

		//Get the ID of the oldest message ID
		$maxID = postInt("oldest_message_id") - 1;

		//Get the limit
		$limit = postInt("limit");

		//Security
		if($limit < 1)
			$limit = 1;
		if($limit > 30)
			$limit = 30;
		
		//Retrieve the list of messages in the database
		$messages = components()->conversations->getOlderMessages($conversationID, $maxID, $limit);

		//Process the list of messages
		foreach($messages as $num => $process)
			$messages[$num] = self::ConvMessageToAPI($process);
		
		return $messages;
	}

	/**
	 * Get the number of unread conversations of the user
	 * 
	 * @url POST conversations/get_number_unread
	 */
	public function get_number_unread(){

		user_login_required();

		//Get the number of unread conversations of the user
		$number_unread_conversations = components()->conversations->number_user_unread(userID);

		//Return result
		return array("nb_unread" => $number_unread_conversations);
	}

	/**
	 * Get the list of unread notifications
	 * 
	 * @url POST /conversations/get_list_unread
	 */
	public function get_list_unread(){

		user_login_required();

		//Get the list of unread conversations of the user
		$list_unread_conversations = components()->conversations->get_list_unread(userID);

		//Process the results
		foreach($list_unread_conversations as $num => $conv)
			$list_unread_conversations[$num] = self::UnreadConversationToAPI($conv);

		//Return result
		return $list_unread_conversations;
	}

	/**
	 * Delete a conversation
	 * 
	 * @url POST /conversations/delete
	 */
	public function delete(){

		user_login_required();

		//Get conversation ID
		$conversationID = getPostConversationID("conversationID");

		//Check if the user belongs to the conversation
		if(!CS::get()->components->conversations->userBelongsTo(userID, $conversationID))
			Rest_fatal_error(401, "Specified user doesn't belongs to the conversation number ".$conversationID." !");
		
		//Try to remove the user from the conversation (delete the conversation if the user
		// is its owner)
		if(!components()->conversations->removeUserFromConversation(userID, $conversationID))
			Rest_fatal_error(500, "An error occured while trying to delete the conversation!");


		//The operation is a success
		return array("success" => "The conversation has been deleted");
	}

	/**
	 * Update the content of a conversation message
	 * 
	 * @url POST /conversations/updateMessage
	 */
	public function updateMessage(){
		user_login_required();

		$messageID = postInt("messageID");
		$newContent = postString("content");

		if(!check_string_before_insert($newContent))
			Rest_fatal_error(401, "Invalid new message content!");

		//Check whether the user own or not conversation message
		if(!components()->conversations->isOwnerMessage(userID, $messageID))
			Rest_fatal_error(401, "You do not own this conversation message!");
		
		//Update the message
		$message = new ConversationMessage();
		$message->set_id($messageID);
		$message->set_message($newContent);
		if(!components()->conversations->updateMessage($message))
			Rest_fatal_error(500, "Could not update the content of the message!");
		
		return array("success" => "The conversation message has been successfully updated!");
	}

	/**
	 * Delete a conversation message
	 * 
	 * @url POST /conversations/deleteMessage
	 */
	public function deleteMessage(){
		
		user_login_required();

		$messageID = postInt("messageID");

		//Check whether the user own or not conversation message
		if(!components()->conversations->isOwnerMessage(userID, $messageID))
			Rest_fatal_error(401, "You do not own this conversation message!");
		
		if(!components()->conversations->deleteConversationMessage($messageID))
			Rest_fatal_error(500, "Could not delete conversation message!");
		
		return array("success" => "Conversation message has been successfully deleted!");
	}

	/**
	 * Get and return safely a conversation ID specified in a $_POST Request
	 * 
	 * This methods check the existence of the conversation, but also check if the
	 * user is a member or not of the conversation
	 * 
	 * @param string $name The name of the $_POST field containing the conversation ID
	 * @return int The ID of the conversation
	 */
	private function getSafePostConversationID(string $name) : int {

		user_login_required();

		//Get the ID of the conversation
		$conversationID = getPostConversationID("conversationID");

		//Check if the user belongs to the conversation
		if(!components()->conversations->userBelongsTo(userID, $conversationID))
			Rest_fatal_error(401, "Specified user doesn't belongs to the conversation number ".$conversationID." !");

		return $conversationID;
	}

	/**
	 * Turn a ConversationInfo object into a valid API entry
	 * 
	 * @param ConversationInfo $conv Information about the conversation
	 * @return array Generated conversation object
	 */
	public static function ConvInfoToAPI(ConversationInfo $conv) : array {

		$data = array();

		$data["ID"] = $conv->get_id();
		$data["ID_owner"] = $conv->get_id_owner();
		$data["last_active"] = $conv->get_last_active();
		$data["name"] = $conv->has_name() ? $conv->get_name() : false;
		$data["following"] = $conv->is_following() ? 1 : 0;
		$data["saw_last_message"] = $conv->is_saw_last_message() ? 1 : 0;
		$data["members"] = $conv->get_members();

		return $data;

	}

	/**
	 * Turn ConversationMessage object into API entry
	 * 
	 * @param ConversationMessage $message The message to convert
	 * @return array Valid dataset for the api
	 */
	public static function ConvMessageToAPI(ConversationMessage $message) : array {

		$data = array();

		$data["ID"] = $message->get_id();
		$data["ID_user"] = $message->get_userID();
		$data["time_insert"] = $message->get_time_sent();
		$data["message"] = $message->has_message() ? $message->get_message() : "";
		$data["image_path"] = $message->has_image_path() ? $message->get_image_url() : null;

		return $data;

	}

	/**
	 * Turn UnreadConversation object into API entry
	 * 
	 * @param UnreadConversation $conversation The conversation to convert
	 * @return array Generated entry
	 */
	private static function UnreadConversationToAPI(UnreadConversation $conversation) : array {

		$data = array();

		$data["id"] = $conversation->get_id();
		$data["conv_name"] = $conversation->has_conv_name() ? $conversation->get_conv_name() : "";
		$data["last_active"] = $conversation->get_last_active();
		$data["userID"] = $conversation->get_userID();
		$data["message"] = $conversation->has_message() ? $conversation->get_message() : "";

		return $data;
	}
}