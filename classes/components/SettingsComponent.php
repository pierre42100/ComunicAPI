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

}

//Register component
Components::register("settings", new SettingsComponents());