<?php
/**
 * Account image class
 *
 * @author Pierre HUBERT
 */
class AccountImage {

	/**
	 * @var string accountImageDirConfItem The name of the configuration item that contains the path
	 * to the images
	 */
	const accountImageDirConfItem = "imageAccountPath";

	/**
	 * @var string defaultAccountImage name of the default account image
	 */
	const defaultAccountImage = "0Reverse.png";

	/**
	 * @var string defaultAccountImage name of the error account image
	 */
	const errorAccountImage = "0Red.png";

	/**
	 * Returns the file of an account image
	 *
	 * @param int $userID The ID of the user on which we perform research
	 * @return string The URL pointing on the account image
	 */
	public function getFile(int $userID) : string {
		
		//First, check if the account image exists
		$accountImageFileName = $this->getFileAccountImage($userID);
		if($accountImageFileName != ""){

			//Get account image visibility level
			$visibilityLevel = $this->visibilityLevel($userID);

			//If account image is open or if the user signed in is the user making the request
			if($visibilityLevel == AccountImageSettings::VISIBILITY_OPEN || userID == $userID)
				//Account image is OPEN
				return $accountImageFileName;
			
			//If the user just requires user to be in
			if($visibilityLevel == AccountImageSettings::VISIBILITY_PUBLIC){
				if(userID != 0)
					return $accountImageFileName;
				else
					return self::errorAccountImage;
			}

			//Else users must be friends
			//Check the two persons are friend or not
			if(CS::get()->components->friends->are_friend($userID, userID))
				//User is allowed to access the image
				return $accountImageFileName;
			else
				return self::errorAccountImage;

		}
		else
			//Return default account image
			return self::defaultAccountImage;
	}

	/**
	 * Returns the URL to an account image
	 * 
	 * @param int $userID Target user ID
	 * @return string $url The URL of the account image
	 */
	public function getURL(int $userID) : string {
		return path_user_data(cs()->config->get(self::accountImageDirConfItem).$this->getFile($userID));
	}

	/**
	 * Get visibilty level of the account image
	 *
	 * 1. The user and his friend ONLY
	 * 2. User, friends, and logged in users
	 * 3. Everybody
	 *
	 * @param int $userID The ID of the user on which we perform researchs
	 * @return int The visibility level of the account image
	 */
	private function visibilityLevel(int $userID) : int {
		$filePath = path_user_data(cs()->config->get(self::accountImageDirConfItem)."adresse_avatars/limit_view_".$userID.".txt", TRUE);
		
		//Check restriction file
		if(!file_exists($filePath))
			return AccountImageSettings::VISIBILITY_OPEN; //Everybody by default
		
		//Check for personnalized level
		$fileContent = file_get_contents($filePath);

			//Return visibility level
			return $fileContent;
	}

	/**
	 * Get the file to the user account image, if the user has any
	 * 
	 * @param int $userID Target user ID
	 * @return string The path to the user account image, empty string else
	 */
	private function getFileAccountImage(int $userID) : string {
		$fileName = path_user_data(cs()->config->get(self::accountImageDirConfItem)."adresse_avatars/".$userID.".txt", TRUE);

		if(file_exists($fileName))
			return file_get_contents($fileName);
		else
			return "";
	}
}

//Register class
Components::register("accountImage", new AccountImage());