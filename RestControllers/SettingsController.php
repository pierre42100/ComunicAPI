<?php
/**
 * API Settings controller
 * 
 * @author Pierre HUBERT
 */

class SettingsController {

	/**
	 * Account image visibility levels for the API
	 */
	const AccountImageVisibilityLevels = array(
		AccountImageSettings::VISIBILITY_OPEN => "open",
		AccountImageSettings::VISIBILITY_PUBLIC => "public",
		AccountImageSettings::VISIBILITY_FRIENDS => "friends"
	);

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
			$virtualDirectory = getPostVirtualDirectory("virtualDirectory");

			//Check if the directory is available
			if(!checkVirtualDirectoryAvailability($virtualDirectory, userID, FALSE))
				Rest_fatal_error(401, "The specified directory is not available!");

		}

		//Create and fill a GeneralSettings object with the new values
		$settings = new GeneralSettings();
		$settings->set_id(userID);
		$settings->set_firstName(removeHTMLnodes(postString("firstName", 3)));
		$settings->set_lastName(removeHTMLnodes(postString("lastName", 3)));
		$settings->set_publicPage(postBool("isPublic"));
		$settings->set_openPage(postBool("isOpen"));
		$settings->rationalizePublicOpenStatus();
		$settings->set_allowComments(postBool("allowComments"));
		$settings->set_allowPostsFriends(postBool("allowPostsFromFriends"));
		$settings->set_friendsListPublic(postBool("publicFriendsList"));
		$settings->set_personnalWebsite(postString("personnalWebsite", 0));
		$settings->set_virtualDirectory($virtualDirectory);
		$settings->set_allowComunicMails(postBool("allow_comunic_mails"));
		$settings->set_publicNote(removeHTMLnodes(postString("publicNote", 0)));

		//Check personnal webiste
		if($settings->has_personnalWebsite()){
			if(!filter_var($settings->get_personnalWebsite(), FILTER_VALIDATE_URL))
				Rest_fatal_error(401, "Invalid personnal URL!");
		}

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
		$userDirectory = getPostVirtualDirectory("directory");

		//Check if the directory is available
		if(!checkVirtualDirectoryAvailability($userDirectory, userID, FALSE))
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
	 * Get account image information
	 * 
	 * @url POST /settings/get_account_image
	 */
	public function getAccountImageSettings(){

		//User login required
		user_login_required();

		//Get the settings of the user
		$settings = components()->accountImage->getSettings(userID);

		//Return parsed settings object
		return $this->AccountImageSettingsToAPI($settings);
	}

	/**
	 * Set a new account image for the user
	 * 
	 * @url POST /settings/upload_account_image
	 */
	public function upload_account_image(){

		//Login required
		user_login_required();

		//Check if it is a valid file
		if(!check_post_file("picture"))
			Rest_fatal_error(400, "An error occured while receiving image !");

		//Try to save image
		$file_uri = save_post_image("picture", 0, "avatars", 800, 800);
		
		//Update account image information
		if(!components()->accountImage->update(userID, substr(strrchr($file_uri, "/"), 1)))
			Rest_fatal_error(500, "An error occured while trying to apply new account image !");

		//Success
		return array("success" => "The new account image has been successfully saved!");
	}

	/**
	 * Delete current user account image
	 * 
	 * @url POST /settings/delete_account_image
	 */
	public function delete_account_image(){

		//Login required
		user_login_required();

		//Try to delete user account image
		if(!components()->accountImage->delete(userID)){
			Rest_fatal_error(500, "Could not delete user account image!");
		}

		//Success
		return array("success" => "The account image has been deleted!");
	}

	/**
	 * Update the visibility of user account image
	 * 
	 * @url POST /settings/set_account_image_visibility
	 */
	public function set_account_image_visibility(){

		//Login required
		user_login_required();

		//Get the list of API visibility levels
		$levels = array_flip(self::AccountImageVisibilityLevels);
		$post_visibility = postString("visibility");

		//Get visibility level
		if(!isset($levels[$post_visibility])){
			Rest_fatal_error(401, "Unrecognized visibility level !");
		}

		//Save visibility level
		components()->accountImage->setVisibilityLevel(userID, $levels[$post_visibility]);

		//Success
		return array("success" => "The visibility level of the account image has been updated!");
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
		$data["publicNote"] = $settings->get_publicNote();

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

	/**
	 * Turn a AccountImageSettings object into API object
	 * 
	 * @param AccountImageSettings $settings The settings object to convert
	 * @return array Generated API object
	 */
	private function AccountImageSettingsToAPI(AccountImageSettings $settings) : array {

		$data = array();

		$data["has_image"] = $settings->has_image_path();
		$data["image_url"] = $settings->has_image_path() ? path_user_data($settings->get_image_path()) : null;
		$data["visibility"] = self::AccountImageVisibilityLevels[$settings->get_visibility_level()];

		return $data;

	}

}