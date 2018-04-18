<?php
/**
 * General settings model
 * 
 * @author Pierre HUBERT
 */

class GeneralSettings {

	//Private fields
	private $id;
	private $email;
	private $firstName;
	private $lastName;
	private $publicPage;
	private $openPage;
	private $allowComments;
	private $allowPostsFriends;
	private $allowComunicMails;
	private $friendsListPublic;
	private $virtualDirectory;
	private $personnalWebsite;

	//Set and get user ID
	public function set_id(int $id){
		$this->id = $id;
	}

	public function get_id() : int {
		return $this->id;
	}

	//Set and get the email address of the user
	public function set_email(string $email){
		$this->email = $email;
	}

	public function get_email() : string {
		return $this->email;
	}


	//Set and get the first name of the user
	public function set_firstName(string $firstName){
		$this->firstName = $firstName;
	}

	public function get_firstName() : string {
		return $this->firstName;
	}


	//Set and get the last name of the user
	public function set_lastName(string $lastName){
		$this->lastName = $lastName;
	}

	public function get_lastName() : string {
		return $this->lastName;
	}


	//Set and get the public status of the user page
	public function set_publicPage(bool $publicPage){
		$this->publicPage = $publicPage;
	}

	public function is_publicPage() : bool {
		return $this->publicPage;
	}

	//Set and get the open status of the user page
	public function set_openPage(bool $openPage){
		$this->openPage = $openPage;
	}

	public function is_openPage() : bool {
		return $this->openPage;
	}

	/**
	 * Make sure the public and the open status of the page
	 * are coherent
	 */
	public function rationalizePublicOpenStatus(){
		//Make sure the page is not open if it is not public
		if(!$this->is_publicPage())
			$this->set_openPage(false);
	}

	//Set and get the comments status on user page
	public function set_allowComments(bool $allowComments){
		$this->allowComments = $allowComments;
	}

	public function is_allowComments() : bool {
		return $this->allowComments;
	}

	//Set and get the friends post authorization status on user page
	public function set_allowPostsFriends(bool $allowPostsFriends){
		$this->allowPostsFriends = $allowPostsFriends;
	}

	public function is_allowPostsFriends() : bool {
		return $this->allowPostsFriends;
	}

	//Set and get comunic mails authorization status
	public function set_allowComunicMails(bool $allowComunicMails){
		$this->allowComunicMails = $allowComunicMails;
	}

	public function is_allowComunicMails() : bool {
		return $this->allowComunicMails;
	}

	//Set and get the visibility of the friends list
	public function set_friendsListPublic(bool $friendsListPublic){
		$this->friendsListPublic = $friendsListPublic;
	}

	public function is_friendsListPublic() : bool {
		return $this->friendsListPublic;
	}


	//Set and get the virtual directory of the user
	public function set_virtualDirectory(string $virtualDirectory){
		$this->virtualDirectory = $virtualDirectory == "" ? null : $virtualDirectory;
	}

	public function has_virtualDirectory() : bool {
		return $this->virtualDirectory != null;
	}

	public function get_virtualDirectory() : string {
		return $this->virtualDirectory != null ? $this->virtualDirectory : "null";
	}

	//Set and get the personnal website of the user
	public function set_personnalWebsite(string $personnalWebsite){
		$this->personnalWebsite = $personnalWebsite == "" ? null : $personnalWebsite;
	}

	public function has_personnalWebsite() : bool {
		return $this->personnalWebsite != null;
	}

	public function get_personnalWebsite() : string {
		return $this->personnalWebsite != null ? $this->personnalWebsite : "null";
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