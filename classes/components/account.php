<?php
/** 
 * User account class
 * 
 * @author Pierre HUBERT
 */

class Account {

	/**
	 * @var String $userTable The name of the user table
	 */
	const USER_TABLE = "utilisateurs";

	/**
	 * @var String $userLoginAPItable The name of the table that contains logins performed on the API
	 */
	private $userLoginAPItable = "";

	/**
	 * Public constructor
	 */
	public function __construct(){
		$this->userLoginAPItable = CS::get()->config->get("dbprefix")."API_userLoginToken";
	}

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
		$userInfos = CS::get()->db->select(Account::USER_TABLE, $conditions, $values);

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
		$tableName = $this->userLoginAPItable;
		$insertValues = array(
			"ID_utilisateurs" => $userID,
			"ID_".CS::get()->config->get("dbprefix")."API_ServicesToken" => $serviceID,
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
		$conditions = "WHERE ID_utilisateurs = ? AND ID_".CS::get()->config->get("dbprefix")."API_ServicesToken = ?";
		$values = array(
			$userID,
			$serviceID
		);
		$tokenInfos = CS::get()->db->select($this->userLoginAPItable, $conditions, $values);
		
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
	 * Delete token from given informations
	 *
	 * @param int $userID The ID of the user to delete
	 * @param string $serviceID The service ID
	 * @return bool False if it fails
	 */
	public function deleteUserLoginToken(int $userID, string $serviceID) : bool {

		//Prepare database request
		$condition = "ID_utilisateurs = ? AND ID_".CS::get()->config->get("dbprefix")."API_ServicesToken = ?";
		$values = array(
			$userID,
			$serviceID
		);

		//Try to perform request
		if(!CS::get()->db->deleteEntry($this->userLoginAPItable, $condition, $values))
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
		$tablesName = $this->userLoginAPItable;
		$conditions = "WHERE ".$this->userLoginAPItable.".ID_".CS::get()->config->get("dbprefix")."API_ServicesToken = ? AND ".$this->userLoginAPItable.".token1 = ? AND ".$this->userLoginAPItable.".token2 = ?";
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
		return $userInfos[0]["ID_utilisateurs"];
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
	 * Crypt user password
	 *
	 * @param string $userPassword The password to crypt
	 * @return string The encrypted password
	 */
	public function cryptPassword(string $userPassword) : string {
		return crypt(sha1($userPassword), sha1($userPassword));
	}
}

//Register class
Components::register("account", new Account());