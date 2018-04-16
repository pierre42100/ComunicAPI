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

		print_r($user_settings);

	}

}