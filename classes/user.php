<?php
/**
 * Main user class
 *
 * @author Pierre HUBER
 */

class User{
	/**
	 * Try to login user with returning a service token
	 * 
	 * @param String $email The e-mail address of the user
	 * @param String $password The password of the user
	 * @param String $serviceID The ID of the service
	 * @return String Token if success, false if fails
	 */
	public function generateUserLoginTokens($email, $password, $serviceID){
		//Try to find user ID in the database
		$conditions = "WHERE mail = ? AND password = ?";
		$values = array(
			$email,
			$this->cryptPassword($password)
		);
		$userInfos = CS::get()->db->select("utilisateurs", $conditions, $values);

		//Check if there is anything
		if(count($userInfos) == 0)
			return false; //Not any account was found
		
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
		$tableName = "API_userLoginToken";
		$insertValues = array(
			"ID_utilisateurs" => $userID,
			"ID_API_ServicesToken" => $serviceID,
			"token1" => $token1,
			"token2" => $token2
		);
		if(!CS::get()->db->addLine($tableName, $insertValues))
			return false; //Something went wrong

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
	function getUserLoginTokenByIDs($userID, $serviceID){
		//Prepare database request
		$conditions = "WHERE ID_utilisateurs = ? AND ID_API_ServicesToken = ?";
		$values = array(
			$userID,
			$serviceID
		);
		$tokenInfos = CS::get()->db->select("API_userLoginToken", $conditions, $values);
		
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
	 * @param Array $tokens The tokens to delete
	 * @param String $serviceID The service ID
	 * @return Boolean False if it fails
	 */
	function deleteUserLoginToken(array $tokens, $serviceID){
		//Check the number of given tokens
		if(count($tokens) != 2)
			return false;

		//Prepare database request
		$condition = "token1 = ? AND token2 = ? AND ID_API_ServicesToken = ?";
		$values = array(
			$tokens[0],
			$tokens[1],
			$serviceID
		);

		//Try to perform request
		if(!CS::get()->db->deleteEntry("API_userLoginToken", $condition, $values))
			return false; //Something went wrong during the request
		
		//Everything is ok
		return true;
	}

	/**
	 * Get User Infos from token
	 *
	 * @param Array $tokens The user login tokens
	 * @param String $serviceID The ID of the service
	 * @return Array The result of the function (empty one if it fails)
	 */
	function getUserInfosFromToken(array $tokens, $serviceID): array {
		//Check token number
		if(count($tokens) != 2)
			return array();

		//Prepare database request
		$tablesName = "utilisateurs, API_userLoginToken";
		$conditions = "WHERE utilisateurs.ID = API_userLoginToken.ID_utilisateurs AND API_userLoginToken.ID_API_ServicesToken = ? AND API_userLoginToken.token1 = ? AND API_userLoginToken.token2 = ?";
		$conditionsValues = array(
			$serviceID,
			$tokens[0],
			$tokens[1]
		);
		
		//Perform request
		$userInfos = CS::get()->db->select($tablesName, $conditions, $conditionsValues);
		
		//Check if result is correct or not
		if(count($userInfos) == 0)
			return array(); //No result
		
		//Prepare return
		$return = array();
		$return['userID'] = $userInfos[0]['ID_utilisateurs'];
		$return['firstName'] = $userInfos[0]['nom'];
		$return['lastName'] = $userInfos[0]['prenom'];
		$return['mailAdress'] = $userInfos[0]['mail'];
		$return['accountCreationDate'] = $userInfos[0]['date_creation'];
		$return['publicPage'] = $userInfos[0]['public'];
		$return['openPage'] = $userInfos[0]['pageouverte'];
		$return['noCommentOnHisPage'] = $userInfos[0]['bloquecommentaire'];
		$return['allowPostFromFriendOnHisPage'] = $userInfos[0]['autoriser_post_amis'];
		$return['virtualDirectory'] = $userInfos[0]['sous_repertoire'];
		$return['personnalWebsite'] = $userInfos[0]['site_web'];
		$return['publicFriendList'] = $userInfos[0]['liste_amis_publique'];

		//Return result
		return $return;
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