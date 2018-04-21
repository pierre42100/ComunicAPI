<?php
/**
 * Comments model
 * 
 * @author Pierre HUBERT
 */

class Comment extends BaseUniqueObject {

	//Private fields
	private $userID;
	private $postID;
	private $time_sent;
	private $content;
	private $img_path;
	private $img_url;
	private $likes;
	private $userLike;

	/**
	 * Public constructor
	 */
	public function __construct(){
		//Initialize some values
		$this->postID = 0;
		$this->userID = 0;
		$this->likes = -1;
	}

	//Set and get user ID
	public function set_userID(int $userID){
		$this->userID = $userID;
	}

	public function get_userID() : int {
		return $this->userID;
	}

	//Set and get post ID
	public function set_postID(int $postID){
		$this->postID = $postID;
	}

	public function has_postID() : bool {
		return $this->postID > 0;
	}

	public function get_postID() : int {
		return $this->postID;
	}

	//Set and get creation time
	public function set_time_sent(int $time_sent){
		$this->time_sent = $time_sent;
	}

	public function get_time_sent() : int {
		return $this->time_sent;
	}

	//Set and get content
	public function set_content(string $content){
		$this->content = $content == "" ? null : $content;
	}

	public function has_content() : bool {
		return $this->content != null;
	}

	public function get_content() : string {
		return $this->content != null ? $this->content : "null";
	}

	//Set and get image path
	public function set_img_path(string $img_path){
		$this->img_path = $img_path == "" ? null : $img_path;
	}

	public function has_img_path() : bool {
		return $this->img_path != null;
	}

	public function get_img_path() : string {
		return $this->img_path != null ? $this->img_path : "null";
	}

	//Set and get image url
	public function set_img_url(string $img_url){
		$this->img_url = $img_url == "" ? null : $img_url;
	}

	public function has_img_url() : bool {
		return $this->img_url != null;
	}

	public function get_img_url() : string {
		return $this->img_url != null ? $this->img_url : "null";
	}

	//Set and get the number of likes over the comment
	public function set_likes(int $likes){
		$this->likes = $likes;
	}

	public function has_likes() : bool {
		return $this->likes > -1;
	}

	public function get_likes() : int {
		return $this->likes;
	}

	//Set and get user like status
	public function set_userLike(bool $userLike){
		$this->userLike = $userLike;
	}

	public function get_userLike() : bool {
		return $this->userLike;
	}
}