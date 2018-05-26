<?php
/**
 * User background image class
 *
 * @author Pierre HUBERT
 */
class BackgroundImage {

	/**
	 * @var String Base folder path for account image
	 */
	private $files_path;

	/**
	 * @var String Base URL for account images
	 */
	private $files_url;
	
	/**
	 * @var String Default background image
	 */
	private $defaultFile = "0.jpg";

	/**
	 * Constructor of the class
	 */
	public function __construct(){
		//Set values
		$this->files_path = path_user_data(CS::get()->config->get("backgroundImagePath"), true);
		$this->files_url = path_user_data(CS::get()->config->get("backgroundImagePath"), false);
	}

	/**
	 * Returns the path of a background image
	 *
	 * @param int $userID The ID of the user on which we perform research
	 * @return string The URL pointing on the background image
	 */
	public function getPath(int $userID) : string {
		//First, check if the background image exists
		$backgroundImageRefFile = $this->getPathMetadata($userID);
		if(file_exists($backgroundImageRefFile)){

			//Get background image path and return it
			return $this->files_url.file_get_contents($backgroundImageRefFile);
			
		}
		else {
			//Return default background image
			return $this->files_url.$this->defaultFile;
		}
	}

	/**
	 * Delete the account image of a user (if any)
	 * 
	 * @param int $userID The ID of the target user
	 * @return bool TRUE for a success / FALSE else
	 */
	public function delete(int $userID) : bool {

		//Get the path to the background image
		$refFile = $this->getPathMetadata($userID);

		//Check if ref file exists or not
		if(file_exists($refFile)){

			$file_target = $this->files_path.file_get_contents($refFile);

			//Delete file
			if(file_exists($file_target)){
				if(!unlink($file_target))
					return FALSE;
			}
			
			//Unlink reference file
			return unlink($refFile);

		}

		//Nothing to be done
		else
			return TRUE;

	}

	/**
	 * Get the path to the file containing the path to the background image
	 * 
	 * @param int $userID Target user ID
	 * @return string The path to the file
	 */
	private function getPathMetadata(int $userID) : string {
		return $this->files_path."adresse_imgfond/".$userID.".txt";
	}
}

//Register class
Components::register("backgroundImage", new BackgroundImage());