<?php
/**
 * Friends component
 *
 * @author Pierre HUBERT
 */

class friends {

	/**
	 * @var String $friendsTable The name of the table for friends
	 */
	private $friendsTable = "amis";

	/**
	 * Public construcor
	 */
	public function __construct(){
		//Nothing now
	}

	/**
	 * Get and returns the list of the friends of a user
	 *
	 * @param Integer $userID The ID of the user
	 * @return Array The list of the friends of the user
	 */
	public function getList($userID) : array {
		
		//Prepare the request on the database
		$tableName = $this->friendsTable.", utilisateurs";
		$condition = "WHERE ID_personne = ? AND amis.ID_amis = utilisateurs.ID ORDER BY utilisateurs.last_activity DESC";
		$condValues = array($userID);

		//Specify which fields to get
		$fieldsList = array(
			"utilisateurs.last_activity",
			$this->friendsTable.".ID_amis",
			$this->friendsTable.".actif",
			$this->friendsTable.".abonnement",
		);

		//Perform the request on the database
		$results = CS::get()->db->select($tableName, $condition, $condValues, $fieldsList);

		//Process results
		$friendsList = array();
		foreach($results as $process){
			$friendsList[] = array(
				"ID_friend" => $process["ID_amis"],
				"accepted" => $process["actif"],
				"following" => $process["abonnement"],
				"time_last_activity" => $process["last_activity"]
			);
		}

		//Return result
		return $friendsList;

	}

	/**
	 * Respond to a friendship request
	 *
	 * @param Integer $userID The ID of the user who respond to the request
	 * @param Integer $friendID The ID of the target friend
	 * @param Boolean $accept Defines wether the friend request was accepted or not
	 * @return Boolean True or false depending of the success of the operation
	 */
	 public function respondRequest($userID, $friendID, $accept){
		//If the request is to refuse friendship request, there isn't any security check to perform
		if(!$accept){
			//Perform a request on the database
			$conditions = "ID_personne = ? AND ID_amis = ? AND actif = 0";
			$conditionsValues = array(
				$userID*1,
				$friendID*1
			);

			//Try to perform request
			if(CS::get()->db->deleteEntry($this->friendsTable, $conditions, $conditionsValues))
				return true; //Operation is a success
			else
				return false; //An error occured
		}

		//Else it is a little more complicated
		//First, check the request was really performed
		if(!$this->checkFriendShipRequestExistence($friendID, $userID))
			return false; //There isn't any existing request
		
		//Else we can update the database to accept the request
		//Update the table
		$conditions = "ID_personne = ? AND ID_amis = ? AND actif = 0";
		$whereValues = array(
			$userID*1,
			$friendID*1
		);
		$modifs = array(
			"actif" => 1
		);

		//First update the table
		if(!CS::get()->db->updateDB($this->friendsTable, $conditions, $modifs, $whereValues))
			return false;

		//Then insert the second friend line
		$insertValues = array(
			"ID_personne" => $friendID,
			"ID_amis" => $userID,
			"actif" => 1
		);
		if(!CS::get()->db->addLine($this->friendsTable, $insertValues))
			return false; //An error occurred
		
		//The operation is a success
		return true;
	}

	/**
	 * Check if a friendship request was performed by someone to someone
	 *
	 * @param Integer $userID The user who may have performed a request
	 * @param Integer $friendID The destination of the request
	 * @return Boolean True or false depending of the success of the operation
	 */
	public function checkFriendShipRequestExistence($userID, $friendID){
		//Perform a request on the database
		$conditions = "WHERE ID_personne = ? AND ID_amis = ? AND actif = 0";
		$dataConditions = array(
			$friendID*1,
			$userID*1
		);
		$fieldsList = array("ID");

		//Try to perform request
		$results = CS::get()->db->select($this->friendsTable, $conditions, $dataConditions, $fieldsList);

		//Check for errors
		if($results === false)
			return false; //An error occured
		
		//Else we check the results
		else
			return count($results) === 1;
	}

	/**
	 * Remove a friend from the firends list
	 *
	 * @param int $userID The ID of the user who delete the friend
	 * @param int $friendID The ID of the friend that is being removed from the list
	 * @return bool True if the user was successfully removed / false else
	 */
	public function remove(int $userID, int $friendID) : bool {

		//Delete the friend from the database
		$tableName = $this->friendsTable;
		$conditions = "(ID_personne = ? AND ID_amis = ?) OR (ID_personne = ? AND ID_amis = ?)";
		$condValues = array($userID, $friendID, $friendID, $userID);

		//Try to perform the request
		$success = CS::get()->db->deleteEntry($tableName, $conditions, $condValues);

		return $success;
	}

	/**
	 * Check wether two users are friend or not
	 *
	 * @param $user1 The ID of the first user
	 * @param $user2 The ID of the second user
	 * @return TRUE if the users are friend / FALSE else
	 */
	public function are_friend(int $user1, int $user2) : bool {
		
		//Query the friends table
		$tableName = $this->friendsTable;
		$conditions = "WHERE ID_personne = ? AND ID_amis = ? AND actif = 1";
		$condValues = array($user1, $user2);

		//Try to perform the request
		$response = CS::get()->db->select($tableName, $conditions, $condValues);

		//Return the result
		return count($response) > 0;
	}

	/**
	 * Send a friendship request
	 * 
	 * @param $userID The ID of the user creating the request
	 * @param $targetID The target of the friendship request
	 * @return TRUE in case of success / FALSE else
	 */
	public function send_request(int $userID, int $targetID) : bool {

		//Prepare the insertion
		$tableName = $this->friendsTable;
		$values = array(
			"ID_personne" => $targetID,
			"ID_amis" => $userID,
			"actif" => 0
		);

		//Try to perform the request
		return CS::get()->db->addLine($tableName, $values) == true;

	}

	/**
	 * Delete a friendship request previously created
	 * 
	 * @param $userID The ID of the user removing its request
	 * @param $targetID The ID of the target of the request
	 * @return TRUE in case of success / false else
	 */
	public function remove_request(int $userID, int $targetID) : bool {

		//Prepare the request on the database
		$tableName = $this->friendsTable;
		$conditions = "ID_personne = ? AND ID_amis = ? AND actif = 0";
		$values = array($targetID, $userID);
		
		//Try to perform the request
		return CS::get()->db->deleteEntry($tableName, $conditions, $values);

	}

	/**
	 * Check wether a user has sent a friendship 
	 * request to another friend
	 * 
	 * @param $user The ID of the user supposed to have sent a request
	 * @param $targetUser The ID of the target user
	 * @return TRUE if a request has been sent / FALSE else
	 */
	public function sent_request(int $user, int $targetUser) : bool {

		//Query the friend table
		$tableName = $this->friendsTable;
		$conditions = "WHERE ID_personne = ? AND ID_amis = ? AND actif = 0";
		$condValues = array($targetUser, $user);

		//Try to perform the request
		$response = CS::get()->db->select($tableName, $conditions, $condValues);

		//Return result
		return count($response) > 0;
	}

	/**
	 * Check wether a user is following or not another friend
	 * 
	 * @param $userID The ID of the user supposed to follow another friend
	 * @param $friendID The ID of the friend
	 * @return TRUE if the user is following the other user / FALSE else
	 */
	public function is_following(int $userID, int $friendID) : bool {

		//Query the friend table
		$tableName = $this->friendsTable;
		$conditions = "WHERE ID_personne = ? AND ID_amis = ? AND actif = 1";
		$condValues = array($userID, $friendID);
		$requiredFields = array("abonnement");

		//Try to perform the request
		$response = CS::get()->db->select($tableName, $conditions, $condValues, $requiredFields);

		//Check for result
		if(count($response) < 1)
			return FALSE; //No entry found
		
		return $response[0]['abonnement'] == 1;
	}

	/**
	 * Check wether a friend allows a friend to post text on his page or not
	 * 
	 * @param $userID The ID of the user performing the request
	 * @param $friendID The ID of the friend allowing or denying the user
	 * to post texts on his page
	 * @return TRUE if the user is allowed to post texts on the page / FALSE else
	 */
	public function can_post_text(int $userID, int $friendID) : bool {

		//Query the friend table
		$tableName = $this->friendsTable;
		$conditions = "WHERE ID_personne = ? AND ID_amis = ? AND actif = 1";
		$condValues = array($friendID, $userID);
		$requiredFields = array("autoriser_post_page");

		//Try to perform the request
		$response = CS::get()->db->select($tableName, $conditions, $condValues, $requiredFields);

		//Check for result
		if(count($response) < 1)
			return FALSE; //No entry found
		
		return $response[0]['autoriser_post_page'] == 1;

	}

	/**
	 * Update the following status for a friendship
	 * 
	 * @param int $userID The ID of the user updating the status
	 * @param int $friendID The ID of the target friend
	 * @param boolean $following The new status
	 * @return bool True in case of succcess / false else
	 */
	public function set_following(int $userID, int $friendID, bool $following) : bool {

		//Update the table
		$tableName = $this->friendsTable;
		$conditions = "ID_personne = ? AND ID_amis = ?";
		$conditionsValues = array($userID, $friendID);
		$newValues = array(
			"abonnement" => $following ? 1 : 0
		);

		//Perform the request
		return CS::get()->db->updateDB($tableName, $conditions, $newValues, $conditionsValues);
	}
}

//Register component
Components::register("friends", new friends());