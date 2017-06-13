<?php
/**
 * Conversations component
 *
 * @author Pierre HUBERT
 */

class conversations {

	/**
	 * @var String $conversationListTable Name of the conversation list table 
	 */
	private $conversationListTable;

	/**
	 * @var String $conversationUsersTable Name of the conversation users table 
	 */
	private $conversationUsersTable;


	/**
	 * Public constructor
	 */
	public function __construct(){
		$this->conversationListTable = CS::get()->config->get("dbprefix")."conversations_list";
		$this->conversationUsersTable = CS::get()->config->get("dbprefix")."conversations_users";
	}

	/**
	 * Get the conversations list of a specified user
	 *
	 * @param Integer $userID The ID of the user to get the list
	 * @return Mixed Array in case of result / False else
	 */
	public function getList($userID){

		//Prepare database request
		$tablesName = $this->conversationListTable.", ".$this->conversationUsersTable;
		
		//Prepare conditions
		$tableJoinCondition = $this->conversationListTable.".ID = ".$this->conversationUsersTable.".ID_".$this->conversationListTable."";
		$userCondition = $this->conversationUsersTable.".ID_utilisateurs = ?";
		$orderResults = "ORDER BY ".$this->conversationListTable.".last_active DESC";

		//Compile conditions
		$conditions = "WHERE ".$tableJoinCondition." AND (".$userCondition.") ".$orderResults;
		$conditionsValues = array($userID);
		
		//Fields list
		$requiredFields = array(
			$this->conversationListTable.".ID",
			$this->conversationListTable.".last_active",
			$this->conversationListTable.".name",
			$this->conversationListTable.".ID_utilisateurs AS ID_owner",
			$this->conversationUsersTable.".following",
			$this->conversationUsersTable.".saw_last_message",
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
				"conversation_members" => $this->getConversationMembers($processConversation["ID"]),
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
		$tableName = $this->conversationUsersTable;
		$conditions = "WHERE ID_".$this->conversationListTable." = ?";
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
		if(!CS::get()->db->addLine($this->conversationListTable, $mainInformations))
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
				"ID_".$this->conversationListTable => $conversationID,
				"time_add" => time(),
				"saw_last_message" => 1,
				"ID_utilisateurs" => $processUser,
			);

			//Make user follow the conversation if required
			if($userID == $processUser)
				$userInformations["following"] = ($follow ? 1 : 0);

			//Try to insert user in conversation
			if(!CS::get()->db->addLine($this->conversationUsersTable, $userInformations))
				return 0; //Error
		}

		//Conversation creation is a success
		return $conversationID;
	}

}

//Register component
Components::register("conversations", new conversations());