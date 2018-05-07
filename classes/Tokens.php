<?php
/**
 * Tokens checker class
 *
 * @author Pierre HUBERT
 */

class Tokens{
	
	/**
	 * Check request client tokens
	 *
	 * @return bool Depends of the validity of the tokens
	 */
	public function checkClientRequestTokens() : bool{
		if(!isset($_POST['serviceName']) OR !isset($_POST['serviceToken']))
			return false; //No token specified
		
		//Check tokens
		if(!$serviceInfos = $this->validateClientTokens($_POST['serviceName'], $_POST['serviceToken']))
			return false;

		//Save service ID in a constant
		define("APIServiceID", $serviceInfos["ID"]);

		//Save service domain in a constant (if any)
		if($serviceInfos["clientDomain"] != "")
			define("APIServiceDomain", $serviceInfos["clientDomain"]);

		//Else everything went good
		return true;
	}

	/**
	 * Check client API credentials (tokens)
	 *
	 * @param 	string 	    $serviceName 	The name of the service
	 * @param 	string  	$token 		 	The service's token
	 * @return 	bool / array		    	False or Tokens ID / Depending of validity of credentials
	 */
	private function validateClientTokens(string $serviceName, string $token) {
		//Prepare DataBase request
		$tableName = CS::get()->config->get("dbprefix")."API_ServicesToken";
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
			//Generate client informations
			$clientInformations = array(
				"ID" => $requestResult[0]['ID'],
				"clientDomain" => ($requestResult[0]["client_domain"] == "" ? false : $requestResult[0]["client_domain"])
			);

			//Return API informations
			return $clientInformations;
		}

	}

}