<?php
/**
 * Account image class
 *
 * @author Pierre HUBERT
 */
class accountImage{

	/**
	 * @var String $accountImageURL URL path pointing on image accounts
	 */
	private $accountImageURL;

	/**
	 * @var String $accountImagePath URL path pointing on image accounts
	 */
	private $accountImagePath;

	/**
	 * @var String $defaultAccountImage name of the default account image
	 */
	private $defaultAccountImage = "0Reverse.png";

	/**
	 * @var String $defaultAccountImage name of the error account image
	 */
	private $errorAccountImage = "0Red.png";

	/**
	 * Public constructor
	 */
	public function __construct(){
		//Set values
		$this->accountImageURL = path_user_data(CS::get()->config->get("imageAccountPath"), false);
		$this->accountImagePath = path_user_data(CS::get()->config->get("imageAccountPath"), true);
	}

	/**
	 * Returns the path of an account image
	 *
	 * @param Integer $userID The ID of the user on which we perform research
	 * @return String The URL pointing on the account image
	 */
	public function getPath($userID){
		//First, check if the account image exists
		$accountImageFileName = $this->accountImagePath."adresse_avatars/".$userID.".txt";
		if(file_exists($accountImageFileName)){

			//Get account image path
			$accountImageFile = $this->accountImageURL.file_get_contents($accountImageFileName);

			//Get account image visibility level
			$visibilityLevel = $this->visibilityLevel($userID);

			//If account image is open
			if($visibilityLevel == 3)
				//Account image is OPEN
				return $accountImageFile;
			
			//If the user just requires user to be in
			if($visibilityLevel == 2){
				if(userID != 0)
					return $accountImageFile;
				else
					return $this->accountImageURL.$this->errorAccountImage;
			}

			//Else users must be friends
			if($visibilityLevel == 1){
				//Level not implemented yet
				return $this->accountImageURL.$this->errorAccountImage;
			}
		}
		else
			//Return default account image
			return $this->accountImageURL.$this->defaultAccountImage;
	}

	/**
	 * Get visibilty level of the account image
	 *
	 * 1. The user and his friend ONLY
	 * 2. User, friends, and logged in users
	 * 3. Everybody
	 *
	 * @param Integer $userID The ID of the user on which we perform researchs
	 * @return Integer The visibility level of the account image
	 */
	private function visibilityLevel($userID){
		$filePath = $this->accountImagePath."adresse_avatars/limit_view_".$userID.".txt";
		
		//Check restriction file
		if(!file_exists($filePath))
			return 3; //Everybody by default
		
		//Check for personnalized level
		$fileContent = file_get_contents($filePath);
		if($fileContent == 1 || $fileContent == 2){
			//Return new visibility level
			return $fileContent*1;
		}
		else
			return 3; //Everybody by default
	}
}

//Register class
Components::register("accountImage", new accountImage());