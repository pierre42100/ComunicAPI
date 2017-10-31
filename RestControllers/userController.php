<?php
/**
 * Main user controller file
 *
 * @author Pierre HUBERT
 */

//Enable access to exceptions handler
use \Jacwright\RestServer\RestException;

class userController
{
	/**
	 * Try to connect user and return login tokens
	 * 
	 * @url POST /user/connectUSER
	 */
	public function connectUSER(){
		 //Check variables sent in request
		 if(!isset($_POST['userMail']) OR !isset($_POST['userPassword']))
			throw new RestException(400, "Missing data !");
		
		//Retrieve database connection
		$db = CS::get()->db;;

		//Extract data
		$userMail = $_POST["userMail"];
		$userPassword = $_POST['userPassword'];

		//Try to perform login
		$loginTokens = CS::get()->components->user->generateUserLoginTokens($userMail, $userPassword, APIServiceID, $db);

		if(count($loginTokens) == 0)
			throw new RestException(401, "Invalid e-mail address / password !");

		//Return result with tokens
		return array(
			"success" => "User logged in !",
			"tokens" => array(
				"token1" => $loginTokens[0],
				"token2" => $loginTokens[1],
			),
		);
	 }

	 /**
	  * Request token delete (= disconnectUSER)
	  *
	  * @url POST /user/disconnectUSER
	  */
	  public function disconnectUSER(){

		user_login_required();

		//Try to delete token
		if(!CS::get()->components->user->deleteUserLoginToken(userID, APIServiceID))
			throw new RestException(500, "Something went wrong while trying to logout user !");

		//Everything is ok
		return array("success" => "The user has been disconnected !");
	  }

	/**
	 * Get informations about a user
	 *
	 * @url POST /user/getInfos
	 * @url POST /user/getInfosMultiple
	 * @return array The result
	 */
	public function getUserInfos() : array{
		user_login_required();

		//Determine userID
		if(isset($_POST['userID'])){
			$usersID = array(toInt($_POST['userID']));
		}
		elseif(isset($_POST['usersID'])){
			//Generate users ID list
			$usersID = numbers_list_to_array($_POST['usersID']);
			
			//Check for errors
			if(count($usersID) == 0)
				Rest_fatal_error(400, "No user ID were specified!");
		}
		else
			//No ID specified
			Rest_fatal_error(400, "Please specify at least one user ID !");

		//Check if it is a wide request or not
		if(count($usersID) <= 10)
			//Try to get user infos
			$userInfos = CS::get()->components->user->getMultipleUserInfos($usersID);
		else {
			//Divide request in multiples ones
			$userInfos = array();
			foreach(array_chunk($usersID, 10) as $process_users_ID){
				
				//Get informations about the IDS
				foreach(CS::get()->components->user->getMultipleUserInfos($process_users_ID) as $key=>$val){
					$userInfos[$key] = $val;
				}
			}
		}
		
		//Check if response is empty
		if(count($userInfos) == 0)
			throw new RestException(401, "Couldn't get user data !");
		
		//Return result
		return $userInfos;
	}

	/**
	 * Get current user infos using tokens
	 *
	 * @url POST /user/getCurrentUserID
	 */
	public function getCurrentUserID(){
		user_login_required();

		//Update last user activity
		CS::get()->components->user->updateLastActivity(userID);

		//Return userID
		return array("userID" => userID);
	}
}