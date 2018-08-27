<?php
/**
 * API Clients checker class
 *
 * @author Pierre HUBERT
 */

class APIClients {
	
	/**
	 * Tables name
	 */
	const SERVICES_TOKENS_TABLE = DBprefix."api_services_tokens";
	const USERS_TOKENS_TABLE = DBprefix."api_users_tokens";

	/**
	 * Check request client tokens
	 *
	 * @return bool Depends of the validity of the tokens
	 */
	public function checkClientRequestTokens() : bool {
		if(!isset($_POST['serviceName']) OR !isset($_POST['serviceToken']))
			return false; //No token specified
		
		//Check tokens
		if(!$serviceInfos = $this->validateClientTokens($_POST['serviceName'], $_POST['serviceToken']))
			return false;

		//Save service ID in a constant
		define("APIServiceID", $serviceInfos["id"]);

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
		$tableName = self::SERVICES_TOKENS_TABLE;
		$conditions = "WHERE service_name = ? AND token = ?";
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
				"id" => $requestResult[0]['id'],
				"clientDomain" => ($requestResult[0]["client_domain"] == "" ? false : $requestResult[0]["client_domain"])
			);

			//Return API informations
			return $clientInformations;
		}

	}

	/**
	 * Create new API client
	 * 
	 * @param APIClient $client The client to create
	 * @return bool TRUE for a success / FALSE else
	 */
	public function create(APIClient $client) : bool {

		//Get database entry
		$entry = self::APIClientsToDb($client);

		//Insert the entry in the database
		$tableName = self::SERVICES_TOKENS_TABLE;
		return CS::get()->db->addLine($tableName, $entry);
	}

	/**
	 * Turn a APIClient object into database entry
	 * 
	 * @param APIClientsToDb $client
	 * @return array Generated database entry
	 */
	private static function APIClientsToDb(APIClient $client) : array {

		$data = array();

		$data["time_insert"] = $client->get_time_insert();
		$data["service_name"] = $client->get_name();
		$data["token"] = $client->get_token();
		if($client->has_client_domain())
			$data["client_domain"] = $client->get_client_domain();

		return $data;

	}

}