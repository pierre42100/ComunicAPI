<?php
/**
 * Tokens checker class
 *
 * @author Pierre HUBERT
 */

class Tokens{
	
	/**
	 * Check request tokens
	 *
	 * @return Boolean Depends of the validity of the tokens
	 */
	public function checkRequestTokens(){
		if(!isset($_POST['serviceName']) OR !isset($_POST['serviceToken']))
			return false; //No token specified
		
		//Check tokens
		if(!$serviceID = $this->validateTokens($_POST['serviceName'], $_POST['serviceToken']))
			return false;

		//Save service ID in a constant
		define("APIServiceID", $serviceID);

		//Else everything went good
		return true;
	}

	/**
	 * Check API credentials (tokens)
	 *
	 * @param 	String 	    $serviceName 	The name of the service
	 * @param 	String  	$token 		 	The service's token
	 * @return 	Boolean 			    	False or Tokens ID / Depending of validity of credentials
	 */
	private function validateTokens($serviceName, $token){
		//Prepare DataBase request
		$tableName = "API_ServicesToken";
		$conditions = "WHERE serviceName = ? AND token = ?";
		$values = array(
			$serviceName,
			$token
		);
		//Make request
		$requestResult = CS::get()->db->select($tableName, $conditions, $values);

		//Analyse result
		if(count($requestResult) == 0){
			//There is no available entries
			return false;
		}
		else {
			//The API is correctly identified
			return $requestResult[0]['ID'];
		}

	}

}