<?php
/**
 * Main user class
 *
 * @author Pierre HUBER
 */

class User{

	/**
	 * @var String $userTable The name of the user table
	 */
	private $userTable = "utilisateurs";

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
	 * @param String $email The e-mail address of the user
	 * @param String $password The password of the user
	 * @param String $serviceID The ID of the service
	 * @return String Token if success, false if fails
	 */
	public function generateUserLoginTokens($email, $password, $serviceID) : array{
		//Try to find user ID in the database
		$conditions = "WHERE mail = ? AND password = ?";
		$values = array(
			$email,
			$this->cryptPassword($password)
		);
		$userInfos = CS::get()->db->select($this->userTable, $conditions, $values);

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
	 * @param Integer $userID The ID of the user
	 * @param Integer $serviceID The ID of the service
	 * @return False if it fails, or tokens if success
	 */
	public function getUserLoginTokenByIDs($userID, $serviceID){
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
	 * @param Integer $userID The ID of the user to delete
	 * @param String $serviceID The service ID
	 * @return Boolean False if it fails
	 */
	public function deleteUserLoginToken($userID, $serviceID){

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
	 * @param Array $tokens The user login tokens
	 * @param String $serviceID The ID of the service
	 * @return Integer User ID (0 for a failure)
	 */
	public function getUserIDfromToken($serviceID, array $tokens){
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
	 * Get Single User Infos
	 *
	 * @param Integer $userID The user ID
	 * @return Array The result of the function (user informations) (empty one if it fails)
	 */
	public function getUserInfos($userID) : array {
		//Prepare database request
		$tablesName = $this->userTable;
		$conditions = "WHERE utilisateurs.ID = ?";
		$conditionsValues = array(
			$userID*1,
		);
		
		//Perform request
		$userInfos = CS::get()->db->select($tablesName, $conditions, $conditionsValues);
		
		//Check if result is correct or not
		if(count($userInfos) == 0)
			return array(); //No result
		
		//Return result
		return $this->generateUserInfosArray($userInfos[0]);
	}

	/**
	 * Get Multiple Users Infos
	 *
	 * @param Array $usersID The users ID
	 * @return Array The result of the function (user informations) (empty one if it fails)
	 */
	public function getMultipleUserInfos(array $usersID) : array {
		//Prepare database request
		$tablesName = $this->userTable;
		$conditions = "WHERE (utilisateurs.ID < 0)";
		$conditionsValues = array();

		//Process users
		foreach($usersID as $i=>$process){
			$conditions .= " OR utilisateurs.ID = ?";
			$conditionsValues[] = $process;
		}
		
		//Perform request
		$usersInfos = CS::get()->db->select($tablesName, $conditions, $conditionsValues);
		
		//Check if result is correct or not
		if(count($usersInfos) == 0)
			return array(); //No result
		
		//Process result
		foreach($usersInfos as $processUser){
			$result[$processUser['ID']] = $this->generateUserInfosArray($processUser);
		}

		//Return result
		return $result;
	}

	/**
	 * Generate and return an array containing informations about a user
	 * given the database entry
	 *
	 * @param Array $userInfos The user entry in the database
	 * @return Array The informations ready to be returned
	 */
	private function generateUserInfosArray(array $userInfos) : array{
		//Prepare return
		$return = array();
		$return['userID'] = $userInfos['ID'];
		$return['firstName'] = $userInfos['prenom'];
		$return['lastName'] = $userInfos['nom'];
		$return['accountCreationDate'] = $userInfos['date_creation'];
		$return['publicPage'] = $userInfos['public'];
		$return['openPage'] = $userInfos['pageouverte'];
		$return['allowPostFromFriendOnHisPage'] = $userInfos['autoriser_post_amis'];
		$return['noCommentOnHisPage'] = $userInfos['bloquecommentaire'];
		$return['virtualDirectory'] = $userInfos['sous_repertoire'];
		$return['personnalWebsite'] = $userInfos['site_web'];
		$return['isPublicFriendList'] = $userInfos['liste_amis_publique'];

		//Add account image url
		$return['accountImage'] = CS::get()->components->accountImage->getPath($return['userID']);

		//Only the user may get its mail address
		if(userID === $return['userID'])
			$return['mailAdress'] = $userInfos['mail'];

		//Return result
		return $return;
	}

	/**
	 * Update last user activity time on the network
	 *
	 * @param Integer $userID The ID of the user to update
	 * @return Boolean True for a success
	 */
	public function updateLastActivity($userID){

		//Perform a request on the database
		$tableName = $this->userTable;
		$conditions = "ID = ?";
		$whereValues = array(userID);
		$modifs = array(
			"last_activity" => time()
		);

		if(!CS::get()->db->updateDB($tableName, $conditions, $modifs, $whereValues))
			return false;

		//Success
		return true;
	}

	/**
	 * Check if a user exists or not
	 *
	 * @param Integer $userID The ID of the user to check
	 * @return Boolean Depends of the existence of the user
	 */
	public function exists($userID){
		//Perform a request on the database
		$tableName = $this->userTable;
		$condition = "WHERE ID = ?";
		$condValues = array($userID);
		$requiredFields = array("ID");

		//Try to perform request
		$result = CS::get()->db->select($tableName, $condition, $condValues, $requiredFields);

		//Check for errors
		if($result === false)
			return false; //An error occured
		
		//Check and return result
		return count($result) !== 0;
	}

	/**
	 * Find the user specified by a folder name
	 *
	 * @param string $folder The folder of the research
	 * @return int 0 if no user was found or the ID of the user in case of success
	 */
	public function findByFolder(string $folder) : int {

		//Perform a request on the database
		$tableName = $this->userTable;
		$condition = "WHERE sous_repertoire = ?";
		$condValues = array($folder);
		$requiredFields = array("ID");

		//Try to perform the request
		$result = CS::get()->db->select($tableName, $condition, $condValues, $requiredFields);

		//Check for errors
		if($result === false){
			return 0;
		}

		if(count($result) == 0)
			return 0; //There is no result
		
		//Return result
		return $result[0]["ID"];

	}

	/**
	 * Get a user page visibility level
	 *
	 * @param $id The ID of the user to fetch
	 * @return The visibility level of the user page
	 * - -1 : In case of failure (will make the protection level elevated)
	 * - 0 : The page is private (for user friends)
	 * - 1 : The page is public (for signed in users)
	 * - 2 : The page is open (for everyone)
	 */
	public function getUserVisibilty(int $userID) : int {

		//Perform a request on the database
		$tableName = $this->userTable;
		$condition = "WHERE ID = ?";
		$condValues = array($userID);

		$requiredFields = array(
			"public",
			"pageouverte"
		);

		//Perform the request
		$result = CS::get()->db->select($tableName, $condition, $condValues, $requiredFields);

		if($result === false){
			return -1;
		}

		//Check for a result
		if(count($result) == 0){
			return -1;
		}

		//Check if the page is public
		if($result[0]["public"] == 0)
			return 0;

		//Check if the page is open or not
		if($result[0]["pageouverte"] == 1)
			return 3; //Page open
		else
			return 2; //Public page

	}
	

	/**
	 * Crypt user password
	 *
	 * @param String $userPassword The password to crypt
	 * @return String The encrypted password
	 */
	public function cryptPassword($userPassword){
		return crypt(sha1($userPassword), sha1($userPassword));
	}

}

//Register class
Components::register("user", new User());