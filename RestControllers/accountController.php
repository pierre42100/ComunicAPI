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
	   
		//API limit
		api_limit_query(APILimits::ACTION_LOGIN_FAILED, false);

		//Retrieve database connection
		$db = CS::get()->db;;

		//Extract data
		$userMail = $_POST["userMail"];
		$userPassword = $_POST['userPassword'];

		//Try to perform login
		$loginTokens = CS::get()->components->account->generateUserLoginTokens($userMail, $userPassword, APIServiceID, $db);

		if(count($loginTokens) == 0){
			api_limit_query(APILimits::ACTION_LOGIN_FAILED, true);
			throw new RestException(401, "Invalid e-mail address / password !");
		}
		   

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
	 * Check if an email is already associated with an account or not
	 * 
	 * @url POST /account/exists_email
	 */
	public function existsMail(){

		//Check the given email address
		$email = postEmail("email", 5);
		
		//Check if the email address is already associated with an account
		$email_exists = components()->account->exists_email($email);
		
		return array(
			"exists" => $email_exists
		);
	}

	/**
	 * Check if an account associated with an email address has set up 
	 * security question or not
	 * 
	 * @url POST /account/has_security_questions
	 */
	public function hasSecurityQuestion(){

		//Get account ID
		$userID = $this->getUserIDFromPostEmail("email");

		//Check if the specified account has defined security questions or not
		return array(
			"defined" => components()->settings->has_security_questions($userID)
		);
	}

	/**
	 * Get the security questions of a user using its email address
	 * 
	 * @url POST /account/get_security_questions
	 */
	public function getSecurityQuestions(){

		//Get account ID
		$userID = $this->getUserIDFromPostEmail("email");

		//Check if user has defined security questions
		if(!components()->settings->has_security_questions($userID))
			Rest_fatal_error(401, "Specified user has not set up security questions!");

		//Get the security settings of the user
		$settings = components()->settings->get_security($userID);

		//Check for errors
		if(!$settings->isValid())
			Rest_fatal_error(500, "An error occurred while retrieving security settings of the user!");
		
		//Return the questions of the user
		return array(
			"questions" => array(
				$settings->get_security_question_1(),
				$settings->get_security_question_2()
			)
		);
	}

	/**
	 * Check the security answers given by a user in order to reset its 
	 * password
	 * 
	 * @url POST /account/check_security_answers
	 */
	public function checkSecurityAnswers(){

		//Get account ID
		$userID = $this->getUserIDFromPostEmail("email");

		//Check if user has defined security questions
		if(!components()->settings->has_security_questions($userID))
			Rest_fatal_error(401, "Specified user has not set up security questions!");

		//Get the security settings of the user
		$settings = components()->settings->get_security($userID);

		//Check for errors
		if(!$settings->isValid())
			Rest_fatal_error(500, "An error occurred while retrieving security settings of the user!");

		//Get the list of security answers
		$answersString = postString("answers", 3);

		//Get answers
		$answers = explode("&", $answersString);

		//Check the number of given answers
		if(count($answers) != 2)
			Rest_fatal_error(401, "Please specify 2 security answers!");
			
		//Check the security answers
		if(strtolower(urldecode($answers[0])) != strtolower($settings->get_security_answer_1()) || 
			strtolower(urldecode($answers[1])) != strtolower($settings->get_security_answer_2()))
			Rest_fatal_error(401, "Specified security answers are invalid!");
		
		//If we get there, security anwsers are valid
		$token = random_str(255);
		if(!components()->account->set_new_password_reset_token($userID, $token))
			Rest_fatal_error(500, "Could not set a password reset token for the account!");
		
		//Return result
		return array(
			"reset_token" => $token
		);
	}

	/**
	 * Check the validity of a reset account token
	 * 
	 * @url POST /account/check_password_reset_token
	 */
	public function checkResetAccountToken(){

		//Get user ID
		$userID = $this->getUserIDFromPasswordResetToken("token");
		
		//The token is valid
		return array("success" => "The token is valid.");
	}

	/**
	 * Reset user password using reset token
	 * 
	 * @url POST /account/reset_user_passwd 
	 */
	public function resetPasswordUsingToken(){

		//Get user ID
		$userID = $this->getUserIDFromPasswordResetToken("token");

		//Save new password
		$newPassword = postString("password");
		if(!components()->account->set_new_user_password($userID, $newPassword))
			Rest_fatal_error(500, "Could not update user password!");

		//Cancel password reset token of the password
		components()->account->remove_password_reset_token($userID);

		//Success
		return array("success" => "Your password has been updated!");
	}

	/**
	 * Create an account
	 * 
	 * @url POST /account/create
	 */
	public function createAccount(){

		api_limit_query(APILimits::ACTION_CREATE_ACCOUNT, false);

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
			Rest_fatal_error(409, "The specified email address is already associated with an account!");
		
		//Create new account object
		$newAccount = new NewAccount();
		$newAccount->firstName = removeHTMLnodes($firstName);
		$newAccount->lastName = removeHTMLnodes($lastName);
		$newAccount->email = $email;
		$newAccount->password = $password;

		//Try to create the account
		if(!components()->account->create($newAccount))
			Rest_fatal_error(500, "An error occured while trying to create the account !");

		api_limit_query(APILimits::ACTION_CREATE_ACCOUNT, true);

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


		//Find the users to fetch information about too
		$users = array();
		$add_user_id = function(int $userID, array &$list){
			if(!in_array($userID, $list))
				$list[] = $userID;
		};
		
		//Friends
		foreach($data["friends_list"] as $friend)
			$add_user_id($friend->getFriendID(), $users);
		
		//Posts
		foreach($data["posts"] as $num => $post){
			$add_user_id($post->get_userID(), $users);

			//Process post comments
			if($post->has_comments()){
				foreach($post->get_comments() as $comment)
					$add_user_id($comment->get_userID(), $users);
			}
		}
			
		//Comments
		foreach($data["comments"] as $num => $comment)
			$add_user_id($comment->get_userID(), $users);
		
		//Conversation members
		foreach($data["conversations_list"] as $num => $conversation){
			foreach($conversation->get_members() as $member)
				$add_user_id($member, $users);
		}

		//Conversation messages
		foreach($data["conversations_messages"] as $num => $conversation){
			foreach($conversation as $message)
				$add_user_id($message->get_userID(), $users);
		}

		//Fetch information about related users
		$data["users_info"] = components()->user->getMultipleUserInfos($users);




		//Prepare API return
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

		//All conversations messages from user
		foreach($data["all_conversation_messages"] as $num => $message)
			$data["all_conversation_messages"][$num] = ConversationsController::ConvMessageToAPI($message);

		//Conversations list
		foreach($data["conversations_list"] as $num => $conversation)
			$data["conversations_list"][$num] = ConversationsController::ConvInfoToAPI($conversation);
		
		//Conversation messages
		foreach($data["conversations_messages"] as $convID=>$messages){
			foreach($messages as $num=>$message)
				$data["conversations_messages"][$convID][$num] = ConversationsController::ConvMessageToAPI($message); 
		}

		//Friends list
		foreach($data["friends_list"] as $num => $friend)
			$data["friends_list"][$num] = friendsController::parseFriendAPI($friend);

		//Users information
		foreach($data["users_info"] as $num => $user)
			$data["users_info"][$num] = userController::userToAPI($user);
		
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

		//Try to delete the account
		if(!components()->account->delete(userID))
			Rest_fatal_error(500, "An error occurred while trying to delete your account!");
		
		//Success
		return array("success" => "The user account has been successfully deleted!");

	}

	/**
	 * Get and return the email address associated with an account
	 * from a $_POST request
	 * 
	 * @param string $name The name of the POST field containing the
	 * email address
	 * @return string The email address
	 */
	private function getPostAccountEmail(string $name) : string {

		//Get the email address
		$email = postEmail($name);

		//Check if the email is associated with an account
		if(!components()->account->exists_email($email))
			Rest_fatal_error(404, "Specified email address in '".$name."' not found!");
		
		return $email;

	}

	/**
	 * Get email address from $_POST request and return associated
	 * account ID
	 * 
	 * @param string $name The name of post field containing email
	 * @return int Associated account ID
	 */
	private function getUserIDFromPostEmail(string $name) : int {

		//Get account email
		$email = $this->getPostAccountEmail($name);

		//Get the ID of the assocated account
		$userID = components()->account->getIDfromEmail($email);

		//Check user ID
		if($userID < 1)
			Rest_fatal_error(500, "Could link the email address to an account!");
		
		return $userID;

	}

	/**
	 * Get the ID of a user from a password reset token
	 * 
	 * @param string $name The name of the post field containing token
	 * @return int Associated user ID
	 */
	private function getUserIDFromPasswordResetToken(string $name) : int {

		//Get the token
		$token = postString($name, 10);
		
		//Validate the tokens
		$userID = components()->account->getUserIDfromResetToken($token);

		//Check if the user ID is valid
		if($userID < 1)
			Rest_fatal_error(401, "Invalid token!");

		return $userID;

	}
}