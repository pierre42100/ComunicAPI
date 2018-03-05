<?php
/**
 * Friend model
 * 
 * @author Pierre HUBER
 */

class Friend {

	//Private fields
	private $friendID;
	private $accepted;
	private $following;
	private $last_activity_time;
	private $can_post_texts;

	//Set and get friend ID
	public function setFriendID(int $id){
		$this->friendID = $id;
	}
	public function getFriendID() : int {
		return $this->friendID;
	}

	//Set and get the accepted state of the friendship
	public function setAccepted(bool $accepted){
		$this->accepted = $accepted;
	}
	public function isAccepted() : bool {
		return $this->accepted;
	}

	//Set and get the following state of the friendship
	public function setFollowing(bool $following){
		$this->following = $following;
	}
	public function isFollowing() : bool {
		return $this->following;
	}

	//Set and get the last activity time of the friend
	public function setLastActivityTime(int $time){
		$this->last_activity_time = $time;
	}
	public function getLastActivityTime() : int {
		return $this->last_activity_time;
	}

	//Set and get the post creation authorization status
	public function setCanPostTexts(bool $can_post_texts){
		$this->can_post_texts = $can_post_texts;
	}
	public function canPostTexts() : bool {
		return $this->can_post_texts;
	}
}