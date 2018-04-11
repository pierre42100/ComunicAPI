<?php
/**
 * API Rest controller
 * 
 * @author Pierre HUBERT
 */

class accountController {

	/**
	 * Try to connect user and return login tokens
	 * 
	 * @url POST /user/connectUSER
	 * @url POST /account/login
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
	   $loginTokens = CS::get()->components->account->generateUserLoginTokens($userMail, $userPassword, APIServiceID, $db);

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
	 * @url POST /account/logout
	 */
	 public function disconnectUSER(){

	   user_login_required();

	   //Try to delete token
	   if(!CS::get()->components->account->deleteUserLoginToken(userID, APIServiceID))
		   throw new RestException(500, "Something went wrong while trying to logout user !");

	   //Everything is ok
	   return array("success" => "The user has been disconnected !");
	 }
}