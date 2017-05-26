<?php
/**
 * Account image class
 *
 * @author Pierre HUBERT
 */
class accountImage{
	/**
	 * Public constructor
	 */
	public function __construct(){
		//Nothing now
	}

	/**
	 * Returns the path of an account image
	 *
	 * @param Integer $userID The ID of the user on which we perform research
	 * @return String The URL pointing on the avatar
	 */
	public function getPath($userID){
		return path_account_image("0Reverse.png");
	}
}

//Register class
Components::register("accountImage", new accountImage());