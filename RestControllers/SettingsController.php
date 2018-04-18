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
	 * Set (update) the general account settings
	 * 
	 * @url POST /settings/set_general
	 */
	public function setGeneral(){

		user_login_required(); //Login needed

		//Check the existence of the fields
		//if(!check_post_parametres(array("firstName", "lastName", "isPublic", "isOpen", 
		//	"allowComments", "allowPostsFromFriends", "publicFriendsList", "personnalWebsite", 
		//	"virtualDirectory", "allow_comunic_mails")))
		//	Rest_fatal_error(400, "Please specify all the parametres for this request!");
		
		//Get and check virtual directory
		$virtualDirectory = postString("virtualDirectory", 0);
		if($virtualDirectory != ""){
			$virtualDirectory = getPostUserDirectory("virtualDirectory");

			//Check if the directory is available
			if(!components()->settings->checkUserDirectoryAvailability($virtualDirectory, userID))
				Rest_fatal_error(401, "The specified directory is not available!");

		}

		//Create and fill a GeneralSettings object with the new values
		$settings = new GeneralSettings();
		$settings->set_id(userID);
		$settings->set_firstName(postString("firstName", 3));
		$settings->set_lastName(postString("lastName", 3));
		$settings->set_publicPage(postBool("isPublic"));
		$settings->set_openPage(postBool("isOpen"));
		$settings->rationalizePublicOpenStatus();
		$settings->set_allowComments(postBool("allowComments"));
		$settings->set_allowPostsFriends(postBool("allowPostsFromFriends"));
		$settings->set_friendsListPublic(postBool("publicFriendsList"));
		$settings->set_personnalWebsite(postString("personnalWebsite", 0));
		$settings->set_virtualDirectory($virtualDirectory);
		$settings->set_allowComunicMails(postBool("allow_comunic_mails"));

		//Try to update settings
		if(!components()->settings->save_general($settings))
			Rest_fatal_error(500, "Coud not save user settings!");
		
		//Success
		return array("success" => "The general settings of the user have been successfully saved !");
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
			Rest_fatal_error(401, "The specified directory is not available!");

		//Else the directory is available
		return array("success" => "The directory is available!");
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