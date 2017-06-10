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
				"saw_last_message" => 1
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