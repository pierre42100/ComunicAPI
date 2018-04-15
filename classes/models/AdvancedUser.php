<?php
/**
 * Advanced user information
 * 
 * @author Pierre HUBERT
 */

//User class must be loaded
require_once __DIR__."/User.php";

class AdvancedUser extends User {

	//Private fields
	private $friendListPublic;
	private $personnalWebsite;
	private $disallowComments;
	private $allowPostFromFriends;
	private $creation_time;
	private $backgroundImage;
	private $pageLikes;

	//Get and set friend list visibility level
	public function set_friendListPublic(bool $friendListPublic){
		$this->friendListPublic = $friendListPublic;
	}

	public function is_friendListPublic() : bool {
		return $this->friendListPublic;
	}


	//Get and set the personnal website of the user
	public function set_personnalWebsite(string $personnalWebsite){
		$this->personnalWebsite = $personnalWebsite == "" ? null : $personnalWebsite;
	}

	public function has_personnalWebsite() : bool {
		return $this->personnalWebsite != null;
	}

	public function get_personnalWebsite() : string {
		return $this->personnalWebsite != null ? $this->personnalWebsite : "null";
	}

	//Get and set comment status on user page
	public function set_disallowComments(bool $disallowComments){
		$this->disallowComments = $disallowComments;
	}

	public function is_disallowComments() : bool {
		return $this->disallowComments;
	}

	//Get and set the allow posts on user page from friend status
	public function set_allowPostFromFriends(bool $allowPostFromFriends){
		$this->allowPostFromFriends = $allowPostFromFriends;
	}

	public function is_allowPostFromFriends() : bool {
		return $this->allowPostFromFriends;
	}

	//Set and get the account creation time
	public function set_creation_time(int $creation_time){
		$this->creation_time = $creation_time;
	}

	public function get_creation_time() : int {
		return $this->creation_time;
	}

	//Set and get the background image of the user
	public function set_backgroundImage(string $backgroundImage){
		$this->backgroundImage = $backgroundImage == "" ? null : $backgroundImage;
	}

	public function get_backgroundImage() : string {
		return $this->backgroundImage != null ? $this->backgroundImage : "null";
	}

	//Get and set the number of likes on user page
	public function set_pageLikes(int $pageLikes){
		$this->pageLikes = $pageLikes;
	}

	public function get_pageLikes() : int {
		return $this->pageLikes;
	}
}