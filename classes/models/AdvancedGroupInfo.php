<?php
/**
 * Advanced information about a group model
 * 
 * @author Pierre HUBERT
 */

//Make sure that GroupInfo has already been included
require_once __DIR__."/GroupInfo.php";

class AdvancedGroupInfo extends GroupInfo {

	//Private fields
	private $time_create = -1;
	private $url;
	private $description;
	private $number_likes = -1;
	private $is_liking = false;

	//Get and set the creation time of the group
	public function set_time_create(int $time_create){
		$this->time_create = $time_create;
	}

	public function has_time_create() : bool {
		return $this->time_create > -1;
	}

	public function get_time_create() : int {
		return $this->time_create;
	}

	//Set and get url
	public function set_url(string $url){
		$this->url = $url == "" ? null : $url;
	}

	public function has_url() : bool {
		return $this->url != null;
	}

	public function get_url() : string {
		return $this->url != null ? $this->url : "null";
	}

	//Set and get description
	public function set_description(string $description){
		$this->description = $description == "" ? null : $description;
	}

	public function has_description() : bool {
		return $this->description != null;
	}

	public function get_description() : string {
		return $this->description != null ? $this->description : "null";
	}

	//Set and get the number of likes over the group
	public function set_number_likes(int $number_likes){
		$this->number_likes = $number_likes;
	}

	public function has_number_likes() : bool {
		return $this->number_likes > -1;
	}

	public function get_number_likes() : int {
		return $this->number_likes;
	}

	//Set and get wheter the user is liking the group or not
	public function setLiking(bool $liking){
		$this->is_liking = $liking;
	}
	
	public function isLiking() : bool {
		return $this->is_liking;
	}
}