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
			throw new RestException(401, "Missing data !");
		
		//Retrieve database connection
		$db = CS::get()->db;;

		//Extract data
		$userMail = $_POST["userMail"];
		$userPassword = $_POST['userPassword'];

		//Try to perform login
		$loginTokens = CS::get()->user->generateUserLoginTokens($userMail, $userPassword, APIServiceID, $db);

		if(!$loginTokens)
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
		if(!CS::get()->user->deleteUserLoginToken(userID, APIServiceID))
			throw new RestException(500, "Something went wrong while trying to logout user !");

		//Everything is ok
		return array("success" => "The user has been disconnected !");
	  }

	/**
	 * Get informations about a user
	 *
	 * @url POST /user/getInfos
	 * @return array The result
	 */
	public function getUserInfos() : array{
		user_login_required();

		//Determine userID
		if(!isset($_POST['userID']))
			Rest_fatal_error(400, "Please specify user ID !");
		
		$userID = $_POST['userID']*1;

		//Try to get user infos
		$userInfos = CS::get()->user->getUserInfos($userID);
		
		//Check if response is empty
		if(count($userInfos) == 0)
			throw new RestException(401, "Couldn't get user data !");
		
		//Return result
		return array($userInfos);
	}

	/**
	 * Get multiple users informations
	 *
	 * @url POST /user/getInfosMultiple
	 * @return array The result
	 */
	public function getMultipleUserInfos() : array{
		user_login_required();

		//Determine userID
		if(!isset($_POST['usersID']))
			Rest_fatal_error(400, "Please specify user ID !");
		
		$usersID = array();
		foreach(json_decode($_POST['usersID']) as $userID){
			$usersID[] = $userID*1;
		}

		//Try to get user infos
		$userInfos = CS::get()->user->getUserInfos($userID);
		
		//Check if response is empty
		if(count($userInfos) == 0)
			throw new RestException(401, "Couldn't get user data (maybe user doesn't exists) !");
		
		//Return result
		return array($userInfos);
	}

	/**
	 * Get current user infos using tokens
	 *
	 * @url POST /user/getCurrentUserID
	 */
	public function getCurrentUserID(){
		user_login_required();

		//Return userID
		return array("userID" => userID);
	}
}