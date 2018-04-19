<?php
/**
 * Settings component
 * 
 * @author Pierre HUBERT
 */

class SettingsComponents {

	/**
	 * Get and return general settings of a user
	 * 
	 * @param int $userID The ID of the target user
	 * @return GeneralSettings General settings about the user / invalid object in case
	 * of failure
	 */
	public function get_general(int $userID) : GeneralSettings {

		//Get user database entry
		$entry = $this->getDBUserInfo($userID);

		//Check for error
		if(count($entry) == 0)
			return new GeneralSettings(); //Return invalid object
		
		//Parse database entry into GeneralSettings entry
		return $this->dbToGeneralSettings($entry);
	}

	/**
	 * Save new version of the general settings of a user
	 * 
	 * @param GeneralSettings $settings The settings to save in the database
	 * @return bool TRUE in case of success / FALSE else
	 */
	public function save_general(GeneralSettings $settings) : bool {

		//Convert GeneralSettings object into database entry
		$entry = $this->generalSettingsToDb($settings);

		//Save information in the database
		return $this->saveDBUserInfo($settings->get_id(), $entry);
	}

	/**
	 * Check whether a directory is already linked to a user or not. If yes,
	 * check if it linked to a specified user ID.
	 * 
	 * @param string $directory The directory to check
	 * @param int $userID Target user ID
	 * @return bool TRUE if the directory is available for the current user,
	 * FALSE else
	 */
	public function checkUserDirectoryAvailability(string $directory, int $userID) : bool {

		//Redirect the request to the user component
		$folderUserID = components()->user->findByFolder($directory);

		//Check if the folder is available
		if($folderUserID == 0)
			return TRUE;
		
		//Check else if the user is owning this domain or not
		return $folderUserID == $userID;
	}

	/**
	 * Get and return security settings of a user
	 * 
	 * @param int $userID Target user ID
	 * @return SecuritySettings An object containing the value / invalid object in
	 * case of failure
	 */
	public function get_security(int $userID) : SecuritySettings {

		//Get user database entry
		$entry = $this->getDBUserInfo($userID);

		//Check for error
		if(count($entry) == 0)
			return new SecuritySettings(); //Return invalid object
		
		//Parse database entry into SecuritySettings entry
		return $this->dbToSecuritySettings($entry);
	}

	/**
	 * Get Single User Infos from database and return its information as an array
	 *
	 * @param int $userID The user ID
	 * @return array Information about the user (empty array in case of failure)
	 */
	private function getDBUserInfo(int $userID) : array {
		//Prepare database request
		$tablesName = AccountComponent::USER_TABLE;
		$conditions = "WHERE utilisateurs.ID = ?";
		$conditionsValues = array(
			$userID*1,
		);
		
		//Perform request
		$userInfos = CS::get()->db->select($tablesName, $conditions, $conditionsValues);
		
		//Check if result is correct or not
		if(count($userInfos) == 0)
			return array(); //No result
		
		//Return parsed result
		return($userInfos[0]);
	}

	/**
	 * Save new user information in the database
	 * 
	 * @param int $userID The ID of the user to update
	 * @param array $values The new values to update in the database
	 * @return bool TRUE in case of success / FALSE else
	 */
	private function saveDBUserInfo(int $userID, array $info) : bool {

		//Prepare the request
		$table = AccountComponent::USER_TABLE;
		$conditions = "ID = ?";
		$conditionsValues = array($userID);

		//Perform the request
		return CS::get()->db->updateDB($table, $conditions, $info, $conditionsValues);
	}

	/**
	 * Parse a user information entry into GeneralSettings object
	 * 
	 * @param array $entry The database entry to process
	 * @return GeneralSettings Generated GeneralSettings entry
	 */
	private function dbToGeneralSettings(array $entry) : GeneralSettings {

		$obj = new GeneralSettings();

		$obj->set_id($entry['ID']);
		$obj->set_email($entry['mail']);
		$obj->set_firstName($entry['prenom']);
		$obj->set_lastName($entry['nom']);
		$obj->set_publicPage($entry['public'] == 1);
		$obj->set_openPage($entry['pageouverte'] == 1);
		$obj->set_allowComments($entry['bloquecommentaire'] == 0);
		$obj->set_allowPostsFriends($entry['autoriser_post_amis'] == 1);
		$obj->set_allowComunicMails($entry['autorise_mail']);
		$obj->set_friendsListPublic($entry['liste_amis_publique']);
		$obj->set_virtualDirectory($entry['sous_repertoire'] == null ? "" : $entry['sous_repertoire']);
		$obj->set_personnalWebsite($entry['site_web'] == null ? "" : $entry['site_web']);

		return $obj;

	}

	/**
	 * Turn GeneralSettings object into database entry
	 * 
	 * @param GeneralSettings $settings Settings entry to turn into database entry
	 * @return array Generated entry
	 */
	private function generalSettingsToDb(GeneralSettings $settings) : array {

		$data = array();

		$data["prenom"] = $settings->get_firstName();
		$data["nom"] = $settings->get_lastName();
		$data["public"] = $settings->is_publicPage() ? 1 : 0;
		$data["pageouverte"] = $settings->is_openPage() ? 1 : 0;
		$data["bloquecommentaire"] = $settings->is_allowComments() ? 0 : 1;
		$data["autoriser_post_amis"] = $settings->is_allowPostsFriends() ? 1 : 0;
		$data["autorise_mail"] = $settings->is_allowComunicMails() ? 1 : 0;
		$data["liste_amis_publique"] = $settings->is_friendsListPublic() ? 1 : 0;
		$data["sous_repertoire"] = $settings->has_virtualDirectory() ? $settings->get_virtualDirectory() : "";
		$data["site_web"] = $settings->has_personnalWebsite() ? $settings->get_personnalWebsite() : "";

		return $data;
	}

	/**
	 * Parse a user information entry into SecuritySettings object
	 * 
	 * @param array $entry The database entry to process
	 * @return SecuritySettings Generated SecuritySettings entry
	 */
	private function dbToSecuritySettings(array $entry) : SecuritySettings {

		$obj = new SecuritySettings();

		$obj->set_id($entry['ID']);
		$obj->set_security_question_1($entry["question1"]);
		$obj->set_security_answer_1($entry["reponse1"]);
		$obj->set_security_question_2($entry["question2"]);
		$obj->set_security_answer_2($entry["reponse2"]);

		return $obj;

	}

}

//Register component
Components::register("settings", new SettingsComponents());