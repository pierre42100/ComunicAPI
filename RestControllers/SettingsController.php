<?php
/**
 * API Settings controller
 * 
 * @author Pierre HUBERT
 */

class SettingsController {

	/**
	 * Get general account settings
	 * 
	 * @url POST /settings/get_general
	 */
	public function getGeneral(){

		user_login_required(); //Login needed

		//Fetch user settings
		$user_settings = components()->settings->get_general(userID);

		//Check for error
		if(!$user_settings->isValid())
			Rest_fatal_error(500, "Could not get user settings !");

		//Parse and return settings entry
		return $this->GeneralSettingsToAPI($user_settings);

	}

	/**
	 * Check the availability of a user directory
	 * 
	 * @url POST /settings/check_user_directory_availability
	 */
	public function checkUserDirectoryAvailability() {

		//User login needed
		user_login_required();

		//Get user directory
		$userDirectory = getPostUserDirectory("directory");

		//Check if the directory is available
		if(!components()->settings->checkUserDirectoryAvailability($userDirectory, userID))
			Rest_fatal_error(401, "The specified domain is not available!");

		//Else the domain is available
		return array("success" => "The domain is available!");
	}

	/**
	 * Turn a GeneralSettings object into a valid API object
	 * 
	 * @param GeneralSettings $settings The object to convert
	 * @return array Generated API object
	 */
	private function GeneralSettingsToAPI(GeneralSettings $settings) : array {

		$data = array();

		$data["id"] = $settings->get_id();
		$data["email"] = $settings->get_email();
		$data["firstName"] = $settings->get_firstName();
		$data["lastName"] = $settings->get_lastName();
		$data["is_public"] = $settings->is_publicPage();
		$data["is_open"] = $settings->is_openPage();
		$data["allow_comments"] = $settings->is_allowComments();
		$data["allow_posts_from_friends"] = $settings->is_allowPostsFriends();
		$data["allow_comunic_mails"] = $settings->is_allowComunicMails();
		$data["public_friends_list"] = $settings->is_friendsListPublic();
		$data["virtual_directory"] = $settings->get_virtualDirectory();
		$data["personnal_website"] = $settings->get_personnalWebsite();

		return $data;
	}

}