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
	 * Get security settings
	 * 
	 * Warning !!! This method is really sensitive, please double check any
	 * user input data !
	 * 
	 * @url POST /settings/get_security
	 */
	public function getSecurity(){

		//User login required
		user_login_required();

		//Make sure the password is valid
		check_post_password(userID, "password");

		//Fetch user security settings
		$settings = components()->settings->get_security(userID);

		//Check settings validity
		if(!$settings->isValid())
			Rest_fatal_error(500, "Could not get user security settings!");
		
		//Parse and return settings entry
		return $this->SecuritySettingsToAPI($settings);
	}

	/**
	 * Set (update) security settings
	 * 
	 * Warning !!! This method is really sensitive, please double check any
	 * user input data !
	 * 
	 * @url POST /settings/set_security
	 */
	public function setSecurity(){

		//User login required
		user_login_required();

		//Make sure the password is valid
		check_post_password(userID, "password");

		//Create a security settings object and fill it with the new information
		$settings = new SecuritySettings();
		$settings->set_id(userID);
		$settings->set_security_question_1(postString("security_question_1", 0));
		$settings->set_security_answer_1(postString("security_answer_1", 0));
		$settings->set_security_question_2(postString("security_question_2", 0));
		$settings->set_security_answer_2(postString("security_answer_2", 0));
		
		//Try to update settings
		if(!components()->settings->save_security($settings))
			Rest_fatal_error(500, "Coud not save security settings!");
		
		//Success
		return array("success" => "The security settings of the user have been successfully saved !");
	}

	/**
	 * Update user password
	 * 
	 * @url POST /settings/update_password
	 */
	public function updatePassword(){

		//User login required
		user_login_required();

		//Check the old password
		check_post_password(userID, "oldPassword");

		//Get and save the new password
		$newPassword = postString("newPassword");

		//Try to save password
		if(!components()->account->set_new_user_password(userID, $newPassword))
			Rest_fatal_error(500, "Could not update user password!");
		
		//Success
		return array("success" => "The password has been updated !");
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

	/**
	 * Turn a SecuritySettings object into a valid API object
	 * 
	 * @param SecuritySettings $settings The object to convert
	 * @return array Generated API object
	 */
	private function SecuritySettingsToAPI(SecuritySettings $settings) : array {

		$data = array();

		$data["id"] = $settings->get_id();
		$data["security_question_1"] = $settings->has_security_question_1() ? $settings->get_security_question_1() : "";
		$data["security_answer_1"] = $settings->has_security_answer_1() ? $settings->get_security_answer_1() : "";
		$data["security_question_2"] = $settings->has_security_question_2() ? $settings->get_security_question_2() : "";
		$data["security_answer_2"] = $settings->has_security_answer_2() ? $settings->get_security_answer_2() : "";

		return $data;
	}

}