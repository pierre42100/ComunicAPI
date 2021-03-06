<?php
/** 
 * User account class
 * 
 * @author Pierre HUBERT
 */

class AccountComponent {

	/**
	 * @var String $userTable The name of the user table
	 */
	const USER_TABLE = "utilisateurs";

	/**
	 * Try to login user with returning a service token
	 * 
	 * @param string $email The e-mail address of the user
	 * @param string $password The password of the user
	 * @param int $serviceID The ID of the service
	 * @return array Tokens if success, false if fails
	 */
	public function generateUserLoginTokens(string $email, string $password, int $serviceID) : array{
		//Try to find user ID in the database
		$conditions = "WHERE mail = ? AND password = ?";
		$values = array(
			$email,
			$this->cryptPassword($password)
		);
		$userInfos = CS::get()->db->select(self::USER_TABLE, $conditions, $values);

		//Check if there is anything
		if(count($userInfos) == 0)
			return array(); //Not any account was found
		
		//Extract first value ID
		$userID = $userInfos[0]['ID'];

		//Check if any other token already exists
		$existingTokens = $this->getUserLoginTokenByIDs($userID, $serviceID, CS::get()->db);
		
		if(is_array($existingTokens)){
			//Return result
			return $existingTokens;
		}

		//Generate random tokens
		$token1 = random_str(75);
		$token2 = random_str(75);

		//Insert token in the database
		$tableName = APIClients::USERS_TOKENS_TABLE;
		$insertValues = array(
			"user_id" => $userID,
			"service_id" => $serviceID,
			"token1" => $token1,
			"token2" => $token2
		);
		if(!CS::get()->db->addLine($tableName, $insertValues))
			return array(); //Something went wrong

		//We can return tokens
		return array($token1, $token2);
	}

	/**
	 * Get token with the help of userID and serviceID
	 *
	 * @param int $userID The ID of the user
	 * @param int $serviceID The ID of the service
	 * @return FALSE if it fails, or tokens if success
	 */
	private function getUserLoginTokenByIDs(int $userID, int $serviceID) {
		//Prepare database request
		$conditions = "WHERE user_id = ? AND service_id = ?";
		$values = array(
			$userID,
			$serviceID
		);
		$tokenInfos = CS::get()->db->select(APIClients::USERS_TOKENS_TABLE, $conditions, $values);
		
		if(count($tokenInfos) == 0)
			return false; //There is nobody at this address
		else {
			//Return tokens
			$token1 = $tokenInfos[0]['token1'];
			$token2 = $tokenInfos[0]['token2'];
			return array($token1, $token2);
		}
	}

	/**
	 * Delete login token from given information of a single service
	 *
	 * @param int $userID The ID of the user to delete
	 * @param string $serviceID The service ID
	 * @return bool False if it fails
	 */
	public function deleteUserLoginToken(int $userID, string $serviceID) : bool {

		//Prepare database request
		$condition = "user_id = ? AND service_id = ?";
		$values = array(
			$userID,
			$serviceID
		);

		//Try to perform request
		if(!CS::get()->db->deleteEntry(APIClients::USERS_TOKENS_TABLE, $condition, $values))
			return false; //Something went wrong during the request
		
		//Everything is ok
		return true;
	}

	/**
	 * Delete all the logins tokens of a user - disconnect him from 
	 * all the services he is connected to
	 * 
	 * @param int $userID Target user ID
	 * @return bool TRUE for a success / FALSE else
	 */
	public function deleteAllUserLoginTokens(int $userID) : bool {

		//Prepare database request
		$condition = "user_id = ?";
		$values = array(
			$userID
		);

		//Try to perform request
		if(!CS::get()->db->deleteEntry(APIClients::USERS_TOKENS_TABLE, $condition, $values))
			return false; //Something went wrong during the request
		
		//Everything is ok
		return true;

	}

	/**
	 * Get User ID from token
	 *
	 * @param int $serviceID The ID of the service
	 * @param array $tokens The user login tokens
	 * @return int User ID (0 for a failure)
	 */
	public function getUserIDfromToken(int $serviceID, array $tokens) : int {
		//Check token number
		if(count($tokens) != 2)
			return 0;
		
		//Prepare database request
		$tablesName = APIClients::USERS_TOKENS_TABLE;
		$conditions = "WHERE ".APIClients::USERS_TOKENS_TABLE.".service_id = ? AND ".APIClients::USERS_TOKENS_TABLE.".token1 = ? AND ".APIClients::USERS_TOKENS_TABLE.".token2 = ?";
		$conditionsValues = array(
			$serviceID,
			$tokens[0],
			$tokens[1]
		);
		
		//Perform request
		$userInfos = CS::get()->db->select($tablesName, $conditions, $conditionsValues);
		
		//Check if result is correct or not
		if(count($userInfos) == 0)
			return 0; //No result

		//Return ID
		return $userInfos[0]["user_id"];
	}

	/**
	 * Check whether an email address is linked to an account or not
	 * 
	 * @return bool TRUE if the email is linked to an account / FALSE else
	 */
	public function exists_email(string $email) : bool {

		//Perform an API request
		$tableName = self::USER_TABLE;
		$conditions = "WHERE mail = ?";
		$values = array($email);

		//Return result
		return CS::get()->db->count($tableName, $conditions, $values) > 0;

	}

	/**
	 * Get the ID of the account associated with an email address
	 * 
	 * @param string $email The email address
	 * @return int The ID of the account / -1 in case of failure
	 */
	public function getIDfromEmail(string $email): int {

		//Perform an API request
		$tableName = self::USER_TABLE;
		$conditions = "WHERE mail = ?";
		$values = array($email);

		//Peform the request
		$values = cs()->db->select($tableName, $conditions, $values);

		if(count($values) == 0)
			return -1; //No result found
		
		//Return first value
		return $values[0]["ID"];

	}

	/**
	 * Intend to create an account
	 * 
	 * @param NewAccount $account The new account to create
	 * @return bool TRUE in case of success / FALSE else
	 */
	public function create(NewAccount $newAccount) : bool {

		//Crypt password
		$password = $this->cryptPassword($newAccount->password);
		
		//Set the values
		$values = array(
			"nom" => $newAccount->lastName,
			"prenom" => $newAccount->firstName,
			"date_creation" => mysql_date(),
			"mail" => $newAccount->email,
			"password" => $password
		);

		//Try to insert the user in the database
		return CS::get()->db->addLine(self::USER_TABLE, $values);
	}

	/**
	 * Check if a password is valid for a user
	 * 
	 * @param int $userID Target user ID : The ID of the user to check
	 * @param string $password The password to check
	 * @return bool TRUE if the password is valid / FALSE else
	 */
	public function checkUserPassword(int $userID, string $password){

		//Crypt password
		$password = $this->cryptPassword($password);

		//Prepare request over the database
		$conditions = array(
			"ID" => $userID,
			"password" => $password
		);

		$data = CS::get()->db->splitConditionsArray($conditions);
		$sql_conds = "WHERE ".$data[0];
		$values = $data[1];

		//Perform request and return result
		return CS::get()->db->count(self::USER_TABLE, $sql_conds, $values) > 0;
	}

	/**
	 * Update user password
	 * 
	 * @param int $userID Target user ID
	 * @param string $password The new password to set to the user
	 * @return bool TRUE in case of success / FALSE else
	 */
	public function set_new_user_password(int $userID, string $password) : bool {

		//Crypt the password
		$password = $this->cryptPassword($password);

		//Prepare database update
		$modif = array("password" => $password);

		//Perform the request
		return CS::get()->db->updateDB(self::USER_TABLE, "ID = ?", $modif, array($userID));
	}

	/**
	 * Set new password reset token for an account
	 * 
	 * @param int $userID Target user ID
	 * @param string $token The new token to apply
	 * @return bool TRUE for a success / FALSE else
	 */
	public function set_new_password_reset_token(int $userID, string $token) : bool {
		
		//Prepare database update
		$modifs = array(
			"password_reset_token" => $token,
			"password_reset_token_time_create" => time()
		);

		//Apply update
		return cs()->db->updateDB(self::USER_TABLE, "ID = ?", $modifs, array($userID));
	}

	/**
	 * Delete the password reset token for an account
	 * 
	 * @param int $userID Target user ID
	 * @return bool TRUE for a success / FALSE else
	 */
	public function remove_password_reset_token(int $userID) : bool {
		
		//Prepare database update
		$modifs = array(
			"password_reset_token" => "",
			"password_reset_token_time_create" => 84 //Too low value to be valid
		);

		//Apply update
		return cs()->db->updateDB(self::USER_TABLE, "ID = ?", $modifs, array($userID));
	}

	/**
	 * Associate password reset token with user ID
	 * 
	 * @param string $token The token to associate
	 * @return int The ID of the user / -1 in case of failure
	 */
	public function getUserIDfromResetToken(string $token) : int {

		//Prepare database query
		$conditions = "WHERE password_reset_token = ? AND password_reset_token_time_create > ?";
		$values = array(
			$token,
			time()-60*60*24 //Maximum validity : 24 hours
		);

		//Query the database
		$results = cs()->db->select(self::USER_TABLE, $conditions, $values);

		//Check if there is not any result
		if(count($results) == 0)
			return -1;
		
		//Return first result user ID
		return $results[0]["ID"];
	}

	/**
	 * Crypt user password
	 *
	 * @param string $userPassword The password to crypt
	 * @return string The encrypted password
	 */
	public function cryptPassword(string $userPassword) : string {
		return crypt(sha1($userPassword), sha1($userPassword));
	}

	/**
	 * Get user account data (full export)
	 * 
	 * @param int $userID Target user ID
	 * @return array Information about the user
	 */
	public function export(int $userID) : array {

		$data = array();

		//General account information
		$data["advanced_info"] = components()->user->getUserAdvancedInfo($userID);

		//Posts list (with comments)
		$data["posts"] = components()->posts->getUserEntirePostsList($userID, TRUE);

		//Comments list
		$data["comments"] = components()->comments->getAllUser($userID);

		//Likes
		$data["likes"] = components()->likes->get_all_user($userID);

		//Survey responses
		$data["survey_responses"] = components()->survey->get_all_responses($userID);

		//User movies
		$data["movies"] = components()->movies->get_list($userID);

		//Conversation messages
		$data["all_conversation_messages"] = components()->conversations->getAllUserMessages($userID);

		//Conversations list
		$data["conversations_list"] = components()->conversations->getList($userID);

		//Conversation messages
		$data["conversations_messages"] = array();
		foreach($data["conversations_list"] as $conversation)

			//Get all the messages of the conversation
			$data["conversations_messages"][$conversation->get_ID()] = 
				components()->conversations->getAllMessages($conversation->get_ID());
		

		//Friend list
		$data["friends_list"] = components()->friends->getList($userID);

		return $data;
	}

	/**
	 * Delete user account
	 * 
	 * @param int $userID The ID of the account to delete
	 * @return bool TRUE for a success / FALSE else
	 */
	public function delete(int $userID) : bool {

		/*//Delete all group memberships
		if(!components()->groups->deleteAllUsersGroups($userID))
			return FALSE;

		//Delete user comments
		if(!components()->comments->deleteAllUser($userID))
			return false;

		//Delete user posts
		if(!components()->posts->deleteAllUser($userID))
			return false;

		//Delete user participation in surveys
		if(!components()->survey->cancel_all_user_responses($userID))
			return false;

		//Delete all the likes created by the user
		if(!components()->likes->delete_all_user($userID))
			return false;

		//Delete user movies
		if(!components()->movies->deleteAllUser($userID))
			return FALSE;

		//Delete conversation messages
		if(!components()->conversations->deleteAllUserMessages($userID))
			return FALSE;

		//Remove users from all its conversations
		if(!components()->conversations->deleteAllUserConversations($userID))
			return FALSE;

		//Delete all the notifications related with the user
		if(!components()->notifications->deleteAllRelatedWithUser($userID))
			return FALSE;

		//Delete all user friends, including friendship requests
		if(!components()->friends->deleteAllUserFriends($userID))
			return FALSE;

		//Delete user account image
		if(!components()->accountImage->delete($userID))
			return FALSE;
		
		//Delete all the likes on the user page
		if(!components()->likes->delete_all($userID, Likes::LIKE_USER))
			return FALSE;
		
		if(!components()->backgroundImage->delete($userID))
			return FALSE;

		//Delete connections to all the services
		if(!$this->deleteAllUserLoginTokens($userID))
			return FALSE;*/

		//Delete user from the database
		//WILL BE IMPLEMENTED WHEN LEGACY VERSION WILL BE REMOVED

		exit("Notice: Account deletion should be available soon...");

		//Success
		return FALSE;
	}
}

//Register class
Components::register("account", new AccountComponent());