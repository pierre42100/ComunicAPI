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
		$condition = "WHERE ID_personne = ? AND amis.ID_amis = utilisateurs.ID ORDER BY utilisateurs.last_activity";
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
				"active" => $process["actif"],
				"following" => $process["abonnement"],
				"time_last_activity" => $process["last_activity"]
			);
		}

		//Return result
		return $friendsList;

	}
}

//Register component
Components::register("friends", new friends());