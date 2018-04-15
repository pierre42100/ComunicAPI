<?php
/**
 * User model
 * 
 * @author Pierre HUBERT
 */

class User {

	//Private fields
	private $id;
	private $firstName;
	private $lastName;
	private $publicPage;
	private $openPage;
	private $virtualDirectory;
	private $accountImageURL;

	/**
	 * Public constructor of this object
	 */
	public function __construct(){
		
		//Set the user ID to 0 by default (invalid user)
		$this->set_id(0);

	}

	//Set and get user ID
	private function set_id(int $id){
		$this->id = $id;
	}

	private function get_id() : int {
		return $this->id;
	}


	//Set and get the first name of the user
	private function set_firstName(string $firstName){
		$this->firstName = $firstName;
	}

	private function get_firstName() : string {
		return $this->firstName;
	}


	//Set and get the last name of the user
	private function set_lastName(string $lastName){
		$this->lastName = $lastName;
	}

	private function get_lastName() : string {
		return $this->lastName;
	}


	//Set and get the public status of the user page
	private function set_publicPage(bool $publicPage){
		$this->publicPage = $publicPage;
	}

	private function is_publicPage() : bool {
		return $this->publicPage;
	}

	//Set and get the open status of the user page
	private function set_openPage(bool $openPage){
		$this->openPage = $openPage;
	}

	private function is_openPage() : bool {
		return $this->openPage;
	}


	//Set and get the virtual directory of the user
	private function set_virtualDirectory(string $virtualDirectory){
		$this->virtualDirectory = $virtualDirectory;
	}

	private function get_virtualDirectory() : string {
		return $this->virtualDirectory != null ? $this->virtualDirectory : "null";
	}

	//Set and get the URL pointing of the user account image
	private function set_accountImageURL(string $accountImageURL){
		$this->accountImageURL = $accountImageURL;
	}

	private function get_accountImageURL() : string {
		return $this->accountImageURL;
	}

	/**
	 * Check wether is object is valid or not
	 * 
	 * @return bool TRUE if this object is valid / FALSE else
	 */
	public function isValid() : bool {
		return $this->id > 0;
	}
}