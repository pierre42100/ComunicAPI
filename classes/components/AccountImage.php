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
	 * Returns the file name of an account image
	 *
	 * @param int $userID The ID of the user on which we perform research
	 * @return string The URL pointing on the account image
	 */
	public function getFile(int $userID) : string {

		//First, check if the account image exists
		$accountImageFileName = $this->getFileAccountImage($userID);
		if($accountImageFileName != ""){

			//Get account image visibility level
			$visibilityLevel = $this->getVisibilityLevel($userID);

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
	 * Returns the path to an account image
	 * 
	 * @param int $userID Target user ID
	 * @return string $url The URL of the account image
	 */
	public function getPath(int $userID) : string {
		return cs()->config->get(self::accountImageDirConfItem).$this->getFile($userID);
	}

	/**
	 * Returns the URL to an account image
	 * 
	 * @param int $userID Target user ID
	 * @return string $url The URL of the account image
	 */
	public function getURL(int $userID) : string {
		return path_user_data($this->getPath($userID));
	}

	/**
	 * Check whether a user has an account image or not
	 * 
	 * @param int $userID Target user ID
	 * @return bool TRUE if the user has an account image / FALSE else
	 */
	public function has(int $userID) : bool {
		return $this->getFileAccountImage($userID) != "";
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
	public function getVisibilityLevel(int $userID) : int {
		$filePath = $this->getPathVisibilityFile($userID);
		
		//Check restriction file
		if(!file_exists($filePath))
			return AccountImageSettings::VISIBILITY_OPEN; //Everybody by default
		
		//Check for personnalized level
		$fileContent = file_get_contents($filePath);

		//Return visibility level
		return (int)$fileContent;
	}

	/**
	 * Set (update) the visibility level of a an account image
	 * 
	 * @param int $userID Target user ID
	 * @param int $level New visibility level
	 */
	public function setVisibilityLevel(int $userID, int $level){

		//Get the name of the file that contains visibility levels
		$file = $this->getPathVisibilityFile($userID);

		//Check if the account image is publicy visible
		if($level == AccountImageSettings::VISIBILITY_OPEN){

			//We do not need visibility file
			if(file_exists($file))
				unlink($file);

		}

		//Else append the new value to the file
		file_put_contents($file, $level);

	}

	/**
	 * Get AccountImageSettings for an account
	 * 
	 * @param int $userID ID of the target user
	 * @return AccountImageSettings Generated account settings object
	 */
	public function getSettings(int $userID) : AccountImageSettings {

		//Create and fill UserAccountImage object
		$settings = new AccountImageSettings();
		
		//Add user account image (if any)
		if($this->has(userID)){
			$settings->set_image_path($this->getPath(userID));
		}
		$settings->set_visibility_level($this->getVisibilityLevel(userID));

		return $settings;
	}

	/**
	 * Update the account image of a user
	 * 
	 * @param int $userID The ID of the target user
	 * @param string $fileName The name of the new account image
	 * @return bool TRUE for a success / FALSE else
	 */
	public function update(int $userID, string $fileName) : bool {

		//First, we must delete previous account image (if any)
		if($this->has($userID)){
			if(!$this->delete($userID))
				return FALSE;
		}

		//Save image file name into metadata file
		return (bool) file_put_contents($this->getPathMetadataFile($userID), $fileName);
	}

	/**
	 * Delete user account image
	 * 
	 * @param int $userID The ID of the target user
	 * @return bool TRUE for a success / FALSE else
	 */
	public function delete(int $userID) : bool {

		$path = $this->getFileAccountImage($userID);

		if($path != ""){
			$syspath = path_user_data($this->getPath($userID), TRUE);

			if(file_exists($syspath))
				unlink($syspath);
			
			//Delete metadata file
			unlink($this->getPathMetadataFile($userID));
		}

		//Success by default
		return TRUE;
	}

	/**
	 * Get the file to the user account image, if the user has any
	 * 
	 * @param int $userID Target user ID
	 * @return string The path to the user account image, empty string else
	 */
	private function getFileAccountImage(int $userID) : string {
		$fileName = $this->getPathMetadataFile($userID);

		if(file_exists($fileName))
			return file_get_contents($fileName);
		else
			return "";
	}

	/**
	 * Get the system path of the file that contains the name of the account
	 * image file
	 * 
	 * @param int $userID The ID of the target user
	 * @return string The sys path pointing of the file that contains user account
	 * image
	 */
	private function getPathMetadataFile(int $userID) : string {
		return path_user_data(cs()->config->get(self::accountImageDirConfItem)."adresse_avatars/".$userID.".txt", TRUE);
	}

	/**
	 * Get the system path of the file that contains visibility level restrictions
	 * 
	 * @param int $userID The ID of the target user
	 * @return string The sys path pointing on the file
	 */
	private function getPathVisibilityFile(int $userID) : string {
		return path_user_data(cs()->config->get(
			self::accountImageDirConfItem)."adresse_avatars/limit_view_".$userID.".txt", TRUE);
	}
}

//Register class
Components::register("accountImage", new AccountImage());