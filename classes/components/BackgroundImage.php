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
	 * @param Integer $userID The ID of the user on which we perform research
	 * @return String The URL pointing on the background image
	 */
	public function getPath(int $userID) : string {
		//First, check if the background image exists
		$backgroundImageRefFile = $this->files_path."adresse_imgfond/".$userID.".txt";
		if(file_exists($backgroundImageRefFile)){

			//Get background image path and return it
			return $this->files_url.file_get_contents($backgroundImageRefFile);
			
		}
		else {
			//Return default background image
			return $this->files_url.$this->defaultFile;
		}
	}

}

//Register class
Components::register("backgroundImage", new BackgroundImage());