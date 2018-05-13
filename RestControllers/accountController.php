<?php
/**
 * API Rest controller
 * 
 * @author Pierre HUBERT
 */

//Enable access to exceptions handler
use \Jacwright\RestServer\RestException;

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

	/**
	 * Create an account
	 * 
	 * @url POST /account/create
	 */
	public function createAccount(){

		//Check post fields existence
		if(!check_post_parametres(array("emailAddress", "firstName", "lastName", "password")))
			Rest_fatal_error(400, "Please check given parameters");

		//Check the first and the last name of the user
		$firstName = $_POST["firstName"];
		$lastName = $_POST["lastName"];
		if(strlen($firstName) < 2 || strlen($lastName) < 2)
			Rest_fatal_error(400, "Please check the length of the first and the last name");

		//Check the given email address
		$email = $_POST['emailAddress'];
		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
			Rest_fatal_error(400, "Specified email address is invalid !");
		
		//Check the given password
		$password = $_POST["password"];
		if(strlen($password) < 3)
			Rest_fatal_error(400, "Please specify a stronger password !");

		
		//Check if the email address is already associated with an account
		if(components()->account->exists_email($email))
			Rest_fatal_error(401, "The specified email address is already associated with an account!");
		
		//Create new account object
		$newAccount = new NewAccount();
		$newAccount->firstName = removeHTMLnodes($firstName);
		$newAccount->lastName = removeHTMLnodes($lastName);
		$newAccount->email = $email;
		$newAccount->password = $password;

		//Try to create the account
		if(!components()->account->create($newAccount))
			Rest_fatal_error(500, "An error occured while trying to create the account !");

		//Success
		return array(
			"success" => "The account has been created !"
		);
	}

	/**
	 * Export all account data
	 * 
	 * @url POST /account/export_data
	 */
	public function exportData(){

		//Login & valid password required
		user_login_required();
		check_post_password(userID, "password");

		//Generate and get data set
		$data = components()->account->export(userID);

		//Process data set
		//Advanced user information
		$data["advanced_info"] = userController::advancedUserToAPI($data["advanced_info"]);

		//Posts
		foreach($data["posts"] as $num => $post)
			$data["posts"][$num] = PostsController::PostToAPI($post);
		
		//Comments
		foreach($data["comments"] as $num => $comment)
			$data["comments"][$num] = CommentsController::commentToAPI($comment);

		//Likes
		foreach($data["likes"] as $num => $like)
			$data["likes"][$num] = LikesController::UserLikeToAPI($like);
		
		//Survey responses
		foreach($data["survey_responses"] as $num => $response)
			$data["survey_responses"][$num] = SurveysController::SurveyResponseToAPI($response);
		
		//Movies
		foreach($data["movies"] as $num => $movie)
			$data["movies"][$num] = MoviesController::MovieToAPI($movie);

		//Conversations messages
		foreach($data["conversation_messages"] as $num => $message)
			$data["conversation_messages"][$num] = ConversationsController::ConvMessageToAPI($message);

		//Conversations list
		foreach($data["conversations_list"] as $num => $conversation)
			$data["conversations_list"][$num] = ConversationsController::ConvInfoToAPI($conversation);
		
		//Friends list
		foreach($data["friends_list"] as $num => $friend)
			$data["friends_list"][$num] = friendsController::parseFriendAPI($friend);

		return $data;
	
	}

	/**
	 * Delete an account
	 * 
	 * @url POST /account/delete
	 */
	public function deleteAccount(){

		//Login & valid password required
		user_login_required();
		check_post_password(userID, "password");

		//Try to delet the account
		if(!components()->account->delete(userID))
			Rest_fatal_error(500, "An error occurred while trying to delete your account!");
		
		//Success
		return array("success" => "The user account has been successfully deleted!");

	}
}