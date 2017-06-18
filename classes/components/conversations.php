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
	 * Public constructor
	 */
	public function __construct(){
		$this->conversationsListTable = CS::get()->config->get("dbprefix")."conversations_list";
		$this->conversationsUsersTable = CS::get()->config->get("dbprefix")."conversations_users";
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
	public function create($userID, $follow, array $usersList, $name){
		
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

			//Prepare informations about the user
			$userInformations = array(
				"ID_".$this->conversationsListTable => $conversationID,
				"time_add" => time(),
				"saw_last_message" => 1,
				"ID_utilisateurs" => $processUser,
			);

			//Make user follow the conversation if required
			if($userID == $processUser)
				$userInformations["following"] = ($follow ? 1 : 0);

			//Try to insert user in conversation
			if(!CS::get()->db->addLine($this->conversationsUsersTable, $userInformations))
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
	 * Check if a user is a conversation moderator or not
	 *
	 * @param Integer $userID The ID of the user to check
	 * @param Integer $conversationID The ID of the conversation to check
	 * @return Boolean True if the user is a conversation moderator or not
	 */

}

//Register component
Components::register("conversations", new conversations());