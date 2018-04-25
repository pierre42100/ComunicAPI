<?php
/**
 * Conversations component
 *
 * @author Pierre HUBERT
 */

class Conversations {

	/**
	 * @var String $conversationsListTable Name of the conversation list table 
	 */
	private $conversationsListTable;

	/**
	 * @var String $conversationsUsersTable Name of the conversation users table 
	 */
	private $conversationsUsersTable;

	/**
	 * @var String $conversationMessagesTabel Name of the conversation messages table
	 */
	private $conversationsMessagesTable;


	/**
	 * Public constructor
	 */
	public function __construct(){
		$this->conversationsListTable = CS::get()->config->get("dbprefix")."conversations_list";
		$this->conversationsUsersTable = CS::get()->config->get("dbprefix")."conversations_users";
		$this->conversationsMessagesTable = CS::get()->config->get("dbprefix")."conversations_messages";
	}

	/**
	 * Get the conversations list of a specified user
	 * or get informations of a specific conversation
	 *
	 * @param int $userID The ID of the user to get the list
	 * @param int $conversationID Optionnal, the ID of conversation to get informatios from
	 * @return Mixed Array in case of result / False else
	 */
	public function getList(int $userID, int $conversationID = 0){

		//Prepare database request
		$tablesName = $this->conversationsListTable.", ".$this->conversationsUsersTable;
		
		//Prepare conditions
		$tableJoinCondition = $this->conversationsListTable.".ID = ".$this->conversationsUsersTable.".ID_".$this->conversationsListTable."";
		$userCondition = $this->conversationsUsersTable.".ID_utilisateurs = ?";
		$orderResults = "ORDER BY ".$this->conversationsListTable.".last_active DESC";

		//Specify conditions values
		$conditionsValues = array($userID);

		//Check if we have to get informations about just one conversation
		if($conversationID != 0){
			$specificConditions = "AND ".$this->conversationsListTable.".ID  = ?";
			$conditionsValues[] = $conversationID;
		}
		else
			$specificConditions = ""; //Nothing now

		//Compile conditions
		$conditions = "WHERE ".$tableJoinCondition." AND (".$userCondition.") ".$specificConditions." ".$orderResults;
		
		//Fields list
		$requiredFields = array(
			$this->conversationsListTable.".ID",
			$this->conversationsListTable.".last_active",
			$this->conversationsListTable.".name",
			$this->conversationsListTable.".ID_utilisateurs AS ID_owner",
			$this->conversationsUsersTable.".following",
			$this->conversationsUsersTable.".saw_last_message",
		);

		//Perform database request
		$results = CS::get()->db->select($tablesName, $conditions, $conditionsValues, $requiredFields);

		//Check for errors
		if($results === false)
			return false; //An error occurred

		//Process results
		$conversationsList = array();
		foreach($results as $entry){
			$conversationsList[] = $this->dbToConvInfo($entry);
		}

		//Return results
		return $conversationsList;
	}


	/**
	 * Get a conversation members
	 *
	 * @param int $conversationID The ID of the conversation
	 * @return array A list of the conversation members (empty arary may means that an error occured)
	 */
	public function getConversationMembers(int $conversationID) : array {

		//Perform a request on the database
		$tableName = $this->conversationsUsersTable;
		$conditions = "WHERE ID_".$this->conversationsListTable." = ?";
		$conditionsValues = array($conversationID*1);
		$getFields = array("ID_utilisateurs as userID");

		//Perform the request
		$results = CS::get()->db->select($tableName, $conditions, $conditionsValues, $getFields);

		if($results === false)
			return array(); //An error occured

		//Process results
		$membersList = array();

		foreach($results as $processUser)
			$membersList[] = $processUser["userID"];

		//Return result
		return $membersList;
	}

	/**
	 * Create a new conversation
	 *
	 * @param ConversationInfo $conv Information about the conversation to create
	 * @return int 0 for a fail else the ID of the newly created conversation
	 */
	public function create(ConversationInfo $conv) : int{
		
		$mainInformations = array(
			"ID_utilisateurs" => $conv->get_id_owner(),
			"name" => ($conv->has_name() ? $conv->get_name() : ""),
			"last_active" => time(),
			"creation_time" => time()
		);

		//First, insert the conversation in the main table
		if(!CS::get()->db->addLine($this->conversationsListTable, $mainInformations))
			return 0; //An error occured
		
		//Get the last inserted ID
		$conversationID = CS::get()->db->getLastInsertedID();

		//Check for errors
		if($conversationID == 0)
			return 0;
		
		//Insert users registrattions
		foreach($conv->get_members() as $processUser){

			//Make user follow the conversation if required
			$processUserFollowing = true;
			if($conv->get_id_owner() == $processUser)
				$processUserFollowing = $conv->is_following();

			//Try to insert user in conversation
			if(!$this->addMember($conversationID, $processUser, $processUserFollowing))
				return 0; //Error
		}

		//Conversation creation is a success
		return $conversationID;
	}

	/**
	 * Check if a user is a member of a conversation or not
	 *
	 * @param int $userID The ID of the user to check
	 * @param int $conversationID The ID of the conversation to check
	 * @return bool True if the user belongs to the conversation
	 */
	public function userBelongsTo(int $userID, int $conversationID) : bool {
		
		//Prepare a request on the database
		$tableName = $this->conversationsUsersTable;
		$conditions = "WHERE ID_".$this->conversationsListTable." = ? AND ID_utilisateurs = ?";
		$values = array(
			$conversationID,
			$userID
		);

		//Peform a request on the database
		$result = CS::get()->db->count($tableName, $conditions, $values);

		//Check if request failed
		if($result === false)
			return false; // An error occured

		//Analyse result and return it
		return $result != 0;
	}

	/**
	 * Change the follow state of a user on conversation
	 *
	 * @param int $userID The ID to update
	 * @param int $conversationID The ID of the conversation
	 * @param bool $follow Specify if the conversation is followed or not
	 * @return bool True for a success
	 */
	public function changeFollowState(int $userID, int $conversationID, bool $follow) : bool{
		
		//Prepare the request on the database
		$tableName = $this->conversationsUsersTable;
		$conditions = "ID_".$this->conversationsListTable." = ? AND ID_utilisateurs = ?";
		$condVals = array(
			$conversationID,
			$userID
		);

		//Defines modifications
		$modifs = array(
			"following" => $follow ? 1 : 0,
		);
		
		//Update the table
		if(!CS::get()->db->updateDB($tableName, $conditions, $modifs, $condVals))
			return false; //An error occured

		//Success
		return true;
	}

	/**
	 * Change conversation name
	 *
	 * @param int $conversationID The ID of the conversation
	 * @param string $conversationName The name of the conversation
	 * @return bool True for a success
	 */
	public function changeName(int $conversationID, string $conversationName) : bool{
		//Prepare database request
		$tableName = $this->conversationsListTable;
		$conditions = "ID = ?";
		$condVals = array($conversationID);

		//Changes
		$changes = array(
			"name" => $conversationName,
		);

		//Try to perform request
		if(!CS::get()->db->updateDB($tableName, $conditions, $changes, $condVals))
			return false; //An error occured

		//Success
		return true;
	}

	/**
	 * Update conversation members list
	 *
	 * @param int $conversationID The ID of the conversation to update
	 * @param array $conversationMembers The new list of conversation members
	 * @return bool True for a success
	 */
	public function updateMembers(int $conversationID, array $conversationMembers) : bool{

		//Get the current conversation list
		$currentMembers = $this->getConversationMembers($conversationID);

		//Determinate entries to add
		$toAdd = array_diff($conversationMembers, $currentMembers);

		//Determinate entries to remove
		$toRemove = array_diff($currentMembers, $conversationMembers);

		//Add new member
		foreach($toAdd as $processInsert){
			if(!$this->addMember($conversationID, $processInsert))
				return false; //An error occured
		}

		//Remove old members
		foreach($toRemove as $processDelete){
			if(!$this->removeMember($conversationID, $processDelete))
				return false; //An error occured
		}

		//Success
		return true;
	}

	/**
	 * Add a member to the list
	 *
	 * @param int $conversationID The ID of the target conversation
	 * @param int $userID The ID of the user to add to the conversation
	 * @param bool $follow Optionnal, specify if the user will follow or not the conversation
	 * @return bool True for a success
	 */
	private function addMember(int $conversationID, int $userID, bool $follow = false) : bool {

		//Prepare database request
		$tableName = $this->conversationsUsersTable;
		$values = array(
			"ID_".$this->conversationsListTable => $conversationID,
			"ID_utilisateurs" => $userID,
			"time_add" => time(),
			"following" => $follow ? 1 : 0,
			"saw_last_message" => 1
		);

		//Try to perform request
		return CS::get()->db->addLine($tableName, $values);
	}

	/**
	 * Remove a member from the list
	 *
	 * @param int $conversationID The ID of the target conversation
	 * @param int $userID The ID of the user to remove from the conversation
	 * @return bool True for a success
	 */
	private function removeMember(int $conversationID, int $userID) : bool {
		//Prepare database request
		$tableName = $this->conversationsUsersTable;
		$conditions = "ID_".$this->conversationsListTable." = ? AND ID_utilisateurs = ?";
		$values = array(
			$conversationID,
			$userID
		);

		//Try to perform request
		return CS::get()->db->deleteEntry($tableName, $conditions, $values);
	}

	/**
	 * Check if a user is a conversation moderator or not
	 *
	 * @param int $userID The ID of the user to check
	 * @param int $conversationID The ID of the conversation to check
	 * @return bool True if the user is a conversation moderator / false else
	 */
	public function userIsModerator(int $userID, int $conversationID) : bool {
		//Prepare database request
		$tableName = $this->conversationsListTable;
		$conditions = "WHERE ID = ?";
		$values = array($conversationID);
		$requiredFields = array(
			"ID_utilisateurs"
		);

		//Peform a request on the database
		$results = CS::get()->db->select($tableName, $conditions, $values, $requiredFields);

		//Check for errors
		if($results === false)
			return false; // An error occured
		
		//Check if there was results
		if(count($results) === 0)
			return false;

		//Check the first result only
		return $results[0]["ID_utilisateurs"] == $userID;
	}

	/**
	 * Search for a private conversation between two users
	 *
	 * @param int $user1 The first user
	 * @param int $user2 The second user
	 * @return array The list of private conversations
	 */
	public function findPrivate(int $user1, int $user2) : array{
		
		//Prepare database request
		$tableName = $this->conversationsUsersTable." AS table1 JOIN ".
			$this->conversationsUsersTable." AS table2 JOIN ".
			$this->conversationsUsersTable." AS table3";
		
		//Prepare conditions
		$joinCondition = "(table1.ID_".$this->conversationsListTable." = table2.ID_".$this->conversationsListTable.")".
			"AND (table1.ID_".$this->conversationsListTable." = table3.ID_".$this->conversationsListTable.")";
		$whereConditions = "table1.ID_utilisateurs = ? AND table2.ID_utilisateurs = ?";
		$groupCondition = "table1.ID_".$this->conversationsListTable." having count(*) = 2";

		//Conditions values
		$condValues = array($user1, $user2);

		//Required fields
		$requiredFields = array(
			"table1.ID_".$this->conversationsListTable." as conversationID",
		);

		//Build conditions
		$conditions = "ON ".$joinCondition." WHERE ".$whereConditions." GROUP BY ".$groupCondition;

		//Try to perform request
		$results = CS::get()->db->select($tableName, $conditions, $condValues, $requiredFields);

		//Check for errors
		if($results === false)
			return false;
		
		//Prepare return
		$conversationsID = array();
		foreach($results as $processConversation)
			$conversationsID[] = $processConversation["conversationID"];
		
		//Return result
		return $conversationsID;
	}

	/**
	 * Insert a new message in the database
	 *
	 * @param NewConversationMessage $message The message to send
	 * @return bool True for a success
	 */
	private function insertMessage(NewConversationMessage $message) : bool {

		//Prepare values
		$tableName = $this->conversationsMessagesTable;
		$values = array(
			"ID_".$this->conversationsListTable => $message->get_conversationID(),
			"ID_utilisateurs" => $message->get_userID(),
			"time_insert" => time(),
			"message" => $message->has_message() ? $message->get_message() : ""
		);

		//Add image path (if required)
		if($message->has_image_path())
			$values['image_path'] = $message->get_image_path();

		//Try to insert new value in database
		if(!CS::get()->db->addLine($tableName, $values))
			return false; //An error occured

		//Success
		return true;
	}

	/**
	 * Update the last time a conversation was active
	 *
	 * @param int $conversationID The ID of the conversation to update
	 * @param int $time The new time of last activity to set
	 * @return bool True for a success
	 */
	private function updateLastActive(int $conversationID, int $time) : bool{

		//Perform a request on the database
		$tableName = $this->conversationsListTable;
		$conditions = "ID = ?";
		$condVals = array($conversationID);

		//Set new values
		$newValues = array(
			"last_active" => $time,
		);

		//Try to perform a request on the database
		if(!CS::get()->db->updateDB($tableName, $conditions, $newValues, $condVals))
			return false; //An error occured

		//Success
		return true;
	}

	/**
	 * Mark all the users of a conversation as "unread"
	 *
	 * @param int $conversationID The ID of the conversation to update
	 * @param array $exceptions Users that should not be marked as read
	 * @return bool True for a success
	 */
	private function allUsersAsUnread(int $conversationID, array $exceptions) : bool{

		//Prepare request
		$tableName = $this->conversationsUsersTable;
		$conditions = "ID_".$this->conversationsListTable." = ?";
		$condVals = array($conversationID);

		//Remove users exceptions
		foreach($exceptions as $userID){
			$conditions.= " AND ID_utilisateurs != ?";
			$condVals[] = $userID;
		}

		//Define new values
		$newValues = array(
			"saw_last_message" => 0
		);

		//Try to perform a request on the database
		if(!CS::get()->db->updateDB($tableName, $conditions, $newValues, $condVals))
			return false; //An error occured

		//Success
		return true;
	}

	/**
	 * Mark the user of a conversation as "read" for a conversation
	 *
	 * @param int $userID The ID of the user to update
	 * @param int $conversationID The ID of a conversation to update
	 * @return bool True for a success
	 */
	public function markUserAsRead(int $userID, int $conversationID) : bool {

		//Prepare database request
		$tableName = $this->conversationsUsersTable;
		$conditions = "ID_".$this->conversationsListTable." = ? AND ID_utilisateurs = ?";
		$condVals = array(
			$conversationID,
			$userID
		);

		//Define new values
		$newValues = array(
			"saw_last_message" => 1
		);

		//Try to perform a request on the database
		if(!CS::get()->db->updateDB($tableName, $conditions, $newValues, $condVals))
			return false; //An error occured

		//Success
		return true;
	}

	/**
	 * Send a new message
	 *
	 * @param NewConversationMessage $message The message to send
	 * @return bool True for a success
	 */
	public function sendMessage(NewConversationMessage $message) : bool{

		//GUIDE LINE : this method act like a "controller" : it doesn't perform any database operation
		//But it manages all operations (insert message; save image; inform other users; ...)

		//First, try to insert the message
		if(!$this->insertMessage($message))
			return false; //An error occured
		
		//Then, update the last activity of the conversation
		if(!$this->updateLastActive($message->get_conversationID(), time()))
			return false; //An error occured (2)
		
		//Then, set all the users of the conversation as unread
		if(!$this->allUsersAsUnread($message->get_conversationID(), array($message->get_userID())))
			return false; //An error occured (3)

		 //Success
		 return true;
	}

	/**
	 * Get the last messages of a conversation
	 *
	 * @param int $conversationID The ID of the target conversation
	 * @param int $numberOfMessages The number of messages to return
	 * @return array The messages of the conversation
	 */
	public function getLastMessages(int $conversationID, int $numberOfMessages) : array {

		//Define conditions
		$conditions = "WHERE ID_".$this->conversationsListTable." = ? ORDER BY ID DESC LIMIT ".($numberOfMessages*1);
		$condVals = array(
			$conversationID
		);

		//Perform request
		$messages = $this->getMessages($conditions, $condVals);

		//Revert messages order
		$messages = array_reverse($messages);

		//Return messages
		return $messages;
	}

	/**
	 * Get the new messages of a conversation
	 *
	 * @param int $conversationID The ID of the target conversation
	 * @param int $lastMessageID The ID of the last know message
	 * @return array A list of messages
	 */
	public function getNewMessages(int $conversationID, int $lastMessageID) : array {

		//Define conditions
		$conditions = "WHERE ID_".$this->conversationsListTable." = ? AND ID > ? ORDER BY ID";
		$condVals = array(
			$conversationID,
			$lastMessageID
		);

		//Perform request
		$messages = $this->getMessages($conditions, $condVals);

		//Return messages
		return $messages;
	}

	/**
	 * Check whether a conversation exists or not
	 * 
	 * @param int $convID The ID of the conversation to check
	 * @return bool TRUE if it exists / false else
	 */
	public function exist(int $convID) : bool {

		//Perform a request on the database
		$tableName = $this->conversationsListTable;

		return CS::get()->db->count($tableName, "WHERE ID = ?", array($convID)) > 0;

	}

	/**
	 * Delete a conversation
	 * 
	 * Delete all the messages, membership and informations about a conversation
	 * 
	 * @param int $conID The conversation to delete
	 * @return bool True in case of success / False else
	 */
	public function delete_conversation(int $convID) : bool {

		//Get all the messages of the conversation
		$messages = $this->getMessages("WHERE ID_".$this->conversationsListTable." = ?", array($convID));

		//Delete each message
		foreach($messages as $message){
			if(!$this->delete_message($message))
				return false;
		}

		//Delete all the members of the conversation
		if(!$this->delete_all_members($convID))
			return false;

		//Delete the conversation entry itself
		if(!$this->delete_conversation_entry($convID))
			return false;

		//Success
		return true;
	}

	/**
	 * Delete a conversation membership
	 * 
	 * This function is just removing a user from a conversation by 
	 * deleting all the messages the user has posted and removing its 
	 * membership
	 * 
	 * @param int $convID The target conversation
	 * @param int $memberID The ID of the member to delete from the conversation
	 * @return bool True in case of success / false else
	 */
	public function delete_member(int $convID, int $memberID) : bool {

		//Get all the messages of member the conversation
		$messages = $this->getMessages(
			"WHERE ID_".$this->conversationsListTable." = ? AND ID_utilisateurs = ?", 
			array($convID, $memberID));

		//Delete each message
		foreach($messages as $message){
			if(!$this->delete_message($message))
				return false;
		}

		//Delete user membership
		if(!$this->removeMember($convID, $memberID))
			return false;

		//Success
		return true;
	}


	/**
	 * Delete a single message of a conversation
	 * 
	 * @param ConversationMessage $message The message to delete
	 * @return bool True in case of success / false else
	 */
	private function delete_message(ConversationMessage $message) : bool {

		//Check if we have to delete an image
		if($message->has_image_path()){

			//Get system path of the image
			$img_sys_path = path_user_data($message->get_image_path(), true);

			if(file_exists($img_sys_path)){
				unlink($img_sys_path);
			}

		}

		//Delete message from the database
		$conditions = "ID = ?";
		$condValues = array($message->get_id());
		return CS::get()->db->deleteEntry($this->conversationsMessagesTable, $conditions, $condValues);

	}

	/**
	 * Delete all the members of a conversation
	 * 
	 * @param int $convID The target conversation
	 * @return bool TRUE in case of success / false else
	 */
	private function delete_all_members(int $convID) : bool {

		//Prepare request on the database
		$conditions = "ID_".$this->conversationsListTable." = ?";
		$values = array(
			$convID
		);

		//Try to perform request
		return CS::get()->db->deleteEntry($this->conversationsUsersTable, $conditions, $values);

	}

	/**
	 * Delete a conversation entry in the database
	 * 
	 * @param int $convID The target conversation
	 * @return bool True in case of success / false else
	 */
	private function delete_conversation_entry(int $convID) : bool {
		return CS::get()->db->deleteEntry($this->conversationsListTable, "ID = ?", array($convID));
	}

	/**
	 * Get the number of unread conversations of a user
	 * 
	 * @param int $userID Target user ID
	 * @return int The number of unread conversations of the user
	 */
	public function number_user_unread(int $userID) : int {

		//Prepare database request
		$tableName = $this->conversationsUsersTable;
		$conditions = "WHERE ID_utilisateurs = ? AND saw_last_message = 0 AND following = 1";
		$values = array($userID);

		//Perform request and return result
		return CS::get()->db->count($tableName, $conditions, $values);

	}

	/**
	 * Get the list of unread conversations of a user
	 * 
	 * @param int $userID Target user ID
	 * @return array The list of unread conversations of the user
	 */
	public function get_list_unread(int $userID) : array {

		//Perform the request on the server
		$tablesName = $this->conversationsUsersTable." as users, ".$this->conversationsListTable." as list, ".$this->conversationsMessagesTable." as messages";
		$conditions = "WHERE users.ID_utilisateurs = ? AND users.following = 1 AND users.saw_last_message = 0 AND users.ID_comunic_conversations_list = list.ID
		AND list.ID = messages.ID_comunic_conversations_list AND list.last_active = messages.time_insert";
		$values = array($userID);

		//Perform the request
		$results = CS::get()->db->select($tablesName, $conditions, $values);

		//Process the list of results
		$list = array();
		foreach($results as $result){

			//Generate the entry
			$entry = $this->dbToUnreadConversation($result);

			$list[] = $entry;
		}

		return $list;
	}

	/**
	 * Get a list of conversation messages based on specified conditions
	 *
	 * @param string $conditions The conditions of the request
	 * @param array $conditionsValues The values of the conditions (Optionnal)
	 * @return array The list of messages as ConversationMessage objects
	 */
	private function getMessages(string $conditions, array $conditionsValues = array()) : array{

		//Prepare database request
		$tableName = $this->conversationsMessagesTable;

		//Define required fields
		$requiredFields = array(
			"ID",
			"ID_utilisateurs AS ID_user",
			"image_path",
			"message",
			"time_insert"
		);

		//Try to perform request on the database
		$messages = CS::get()->db->select($tableName, $conditions, $conditionsValues, $requiredFields);

		//Process each message
		foreach($messages as $num=>$message)
			$messages[$num] = self::dbToConvMessage($message);

		//Return result
		return $messages;
	}

	/**
	 * Turn a conversation database entry into a ConversationInfo object
	 * 
	 * @param array $entry Conversation entry in the database
	 * @return ConversationInfo Generated conversation information object
	 */
	private function dbToConvInfo(array $entry) : ConversationInfo {

		$conv = new ConversationInfo();

		$conv->set_id($entry["ID"]);
		$conv->set_id_owner($entry["ID_owner"]);
		$conv->set_last_active($entry["last_active"]);
		if($entry["name"] != null)
			$conv->set_name($entry["name"]);
		$conv->set_following($entry["following"] == 1);
		$conv->set_saw_last_message($entry["saw_last_message"] == 1);
		$conv->set_members($this->getConversationMembers($entry["ID"]));

		return $conv;

	}

	/**
	 * Turn a conversation message into ConversationMessage object
	 * 
	 * @param array $entry Conversation entry in the database
	 * @return ConversationMessage Generated ConversationMessage object
	 */
	private function dbToConvMessage(array $entry) : ConversationMessage {

		$message = new ConversationMessage();

		$message->set_id($entry["ID"]);
		$message->set_userID($entry["ID_user"]);
		$message->set_time_sent($entry["time_insert"]);
		if($entry["image_path"] != null)
			$message->set_image_path($entry["image_path"]);
		if($entry["message"] != null)
			$message->set_message($entry["message"]);

		return $message;

	}

	/**
	 * Turn a database entry into a UnreadConversation object
	 * 
	 * @param array $entry Conversation entry in the database
	 * @return UnreadConversation Generated object
	 */
	private function dbToUnreadConversation(array $entry) : UnreadConversation {
		
		$conversation = new UnreadConversation();

		$conversation->set_id($entry["ID_comunic_conversations_list"]);
		if($entry["name"] != null)
			$conversation->set_conv_name($entry["name"]);
		$conversation->set_last_active($entry["last_active"]);
		$conversation->set_userID($entry["ID_utilisateurs"]);
		if($entry["message"] != null)
			$conversation->set_message($entry["message"]);

		return $conversation;

	}

}

//Register component
Components::register("conversations", new Conversations());