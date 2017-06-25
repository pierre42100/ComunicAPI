<?php
/**
 * Conversations component
 *
 * @author Pierre HUBERT
 */

class conversations {

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
	 * @param Integer $userID The ID of the user to get the list
	 * @param Integer $conversationID Optionnal, the ID of conversation to get informatios from
	 * @return Mixed Array in case of result / False else
	 */
	public function getList($userID, $conversationID = 0){

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
		foreach($results as $processConversation){
			$conversationsList[] = array(
				"ID" => $processConversation["ID"],
				"ID_owner" => $processConversation["ID_owner"],
				"last_active" => $processConversation["last_active"],
				"name" => ($processConversation["name"] == "" ? false : $processConversation["name"]),
				"following" => $processConversation["following"],
				"saw_last_message" => $processConversation["saw_last_message"],

				//Get and add conversation members
				"members" => $this->getConversationMembers($processConversation["ID"]),
			);
		}

		//Return results
		return $conversationsList;
	}


	/**
	 * Get a conversation members
	 *
	 * @param Integer $conversationID The ID of the conversation
	 * @return Array A list of the conversation members (empty arary may means that an error occured)
	 */
	public function getConversationMembers($conversationID) : array {

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
	 * @param Integer $userID The ID of the user creating the conversation
	 * @param Boolean $follow Defines if the user creating the conversation will follow it 
	 * @param Array $usersList The list of users following the conversation
	 * @param Mixed $name Optionnal, the name of the conversation
	 * @return Integer 0 for a fail else the ID of the newly created conversation
	 */
	public function create($userID, $follow, array $usersList, $name = ""){
		
		$mainInformations = array(
			"ID_utilisateurs" => $userID*1,
			"name" => ($name ? $name : ""),
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
		foreach($usersList as $processUser){

			//Make user follow the conversation if required
			$processUserFollowing = false;
			if($userID == $processUser)
				$processUserFollowing = $follow;

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
	 * @param Integer $userID The ID of the user to check
	 * @param Integer $conversationID The ID of the conversation to check
	 * @return Boolean True if the user belongs to the conversation
	 */
	public function userBelongsTo($userID, $conversationID){
		
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
	 * @param Integer $userID The ID to update
	 * @param Integer $conversationID The ID of the conversation
	 * @param Boolean $follow Specify if the conversation is followed or not
	 * @return Boolean True for a success
	 */
	public function changeFollowState($userID, $conversationID, $follow){
		
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
	 * @param Integer $conversationID The ID of the conversation
	 * @param String $conversationName The name of the conversation
	 * @return Boolean True for a success
	 */
	public function changeName($conversationID, $conversationName){
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
	 * @param Integer $conversationID The ID of the conversation to update
	 * @param Array $conversationMembers The new list of conversation members
	 * @return Boolean True for a success
	 */
	public function updateMembers($conversationID, array $conversationMembers){

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
	 * @param Integer $conversationID The ID of the target conversation
	 * @param Integer $userID The ID of the user to add to the conversation
	 * @param Boolean $follow Optionnal, specify if the user will follow or not the conversation
	 * @return Boolean True for a success
	 */
	private function addMember($conversationID, $userID, $follow = false){

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
	 * @param Integer $conversationID The ID of the target conversation
	 * @param Integer $userID The ID of the user to remove from the conversation
	 * @return Boolean True for a success
	 */
	private function removeMember($conversationID, $userID){
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
	 * @param Integer $userID The ID of the user to check
	 * @param Integer $conversationID The ID of the conversation to check
	 * @return Boolean True if the user is a conversation moderator / false else
	 */
	public function userIsModerator($userID, $conversationID){
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
	 * @param Integer $user1 The first user
	 * @param Integer $user2 The second user
	 * @return Array The list of private conversations
	 */
	public function findPrivate($user1, $user2) : array{
		
		//Prepare database request
		$tableName = $this->conversationsUsersTable." AS table1 JOIN ".
			$this->conversationsUsersTable." AS table2";
		
		//Prepare conditions
		$joinCondition = "table1.ID_".$this->conversationsListTable." = table2.ID_".$this->conversationsListTable;
		$whereConditions = "table1.ID_utilisateurs = ? OR table1.ID_utilisateurs = ?";
		$groupCondition = "table1.ID_".$this->conversationsListTable." having count(*) = 4";

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
	 * @param Integer $userID The ID of the user inserting the message
	 * @param Integer $conversationID The ID of the target conversation
	 * @param String $message The message to insert
	 * @param Mixed $image_path Optionnal, the path to an image associated with the message
	 * @return Boolean True for a success
	 */
	private function insertMessage($userID, $conversationID, $message, $image_path = false){

		//Prepare values
		$tableName = $this->conversationsMessagesTable;
		$values = array(
			"ID_".$this->conversationsListTable => $conversationID,
			"ID_utilisateurs" => $userID,
			"time_insert" => time(),
			"message" => $message
		);

		//Add image path (if required)
		if($image_path)
			$values['image_path'] = $image_path;

		//Try to insert new value in database
		if(!CS::get()->db->addLine($tableName, $values))
			return false; //An error occured

		//Success
		return true;
	}

	/**
	 * Update the last time a conversation was active
	 *
	 * @param Integer $conversationID The ID of the conversation to update
	 * @param Integer $time The new time of last activity to set
	 * @return Boolean True for a success
	 */
	private function updateLastActive($conversationID, $time){

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
	 * @param Integer $conversationID The ID of the conversation to update
	 * @param Array $exceptions Users that should not be marked as read
	 * @return Boolean True for a success
	 */
	private function allUsersAsUnread($conversationID, array $exceptions){

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
	 * Send a new message
	 *
	 * @param Integer $userID The ID of the user sending the message
	 * @param Integer $conversationID The ID of the target conversation
	 * @param String $message The message
	 * @param Mixed $image_path Optionna, define the path to an image associated with the message
	 * @return Boolean True for a success
	 */
	public function sendMessage($userID, $conversationID, $message, $image_path = false){

		//GUIDE LINE : this method act like a "controller" : it doesn't perform any database operation
		//But it manage all operations (insert message; save image; inform other users; ...)

		//First, try to insert the message
		if(!$this->insertMessage($userID, $conversationID, $message, $image_path))
			return false; //An error occured
		
		//Then, update the last activity of the conversation
		if(!$this->updateLastActive($conversationID, time()))
			return false; //An error occured (2)
		
		//Then, set all the users of the conversation as unread
		if(!$this->allUsersAsUnread($conversationID, array($userID)))
			return false; //An error occured (3)

		 //Success
		 return true;
	}

	/**
	 * Get the last messages of a conversation
	 *
	 * @param Integer $conversationID The ID of the target conversation
	 * @param Integer $numberOfMessages The number of messages to return
	 * @return Array The messages of the conversation
	 */
	public function getLastMessages($conversationID, $numberOfMessages) : array {

		//Prepare database request
		$tableName = $this->conversationsMessagesTable;
		
		//Define conditions
		$conditions = "WHERE ID_".$this->conversationsListTable." = ? ORDER BY ID DESC LIMIT ".($numberOfMessages*1);
		$condVals = array(
			$conversationID
		);

		//Define required fields
		$requiredFields = array(
			"ID",
			"ID_utilisateurs AS ID_user",
			"image_path",
			"message",
			"time_insert"
		);

		//Try to perform request on the database
		$messages = CS::get()->db->select($tableName, $conditions, $condVals, $requiredFields);

		//Process each message
		array_walk($messages, function(&$item){
			//Check if image is not null
			if($item["image_path"] !== null && $item["image_path"] != ""){
				//Replace image name with full URL
				$item["image_path"] = path_user_data($item["image_path"]);
			}
		});

		//Revert messages order
		$messages = array_reverse($messages);

		//Return messages
		return $messages;
	}

}

//Register component
Components::register("conversations", new conversations());