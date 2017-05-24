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
	 * Get current user infos using tokens
	 *
	 * @url POST /user/getCurrentUserInfos
	 * @return array The result
	 */
	public function getCurrentUserInfosWithTokens() : array{
		user_login_required();

		//Try to get user infos from token
		$userInfos = CS::get()->user->getUserInfos(userID);
		
		//Check if response is empty
		if(count($userInfos) == 0)
			throw new RestException(401, "Couldn't get user data !");
		
		//Return result
		return array($userInfos);
	}

	/**
	 * Get current user infos using tokens
	 *
	 * @url POST /user/getCurrentUserID
	 */
	public function getCurrentUserIDUsingTokens(){
		user_login_required();

		//Return userID
		return array("userID" => userID);
	}
}