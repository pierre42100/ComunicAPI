<?php
/**
 * Post model
 * 
 * @author Pierre HUBERT
 */

class Post extends BaseUniqueObjectFromUser {

	//Private fields
	private $user_page_id = -1;
	private $content;
	private $visibility_level;
	private $kind;
	private $file_size = -1;
	private $file_type;
	private $file_path;
	private $file_path_url;
	private $video_id = -1;
	private $video_info;
	private $time_end = -1;
	private $link_url;
	private $link_title;
	private $link_description;
	private $link_image;
	private $data_survey;
	private $likes = -1;
	private $userLike;
	private $comments;
	private $user_access;

	
	//Set and get the target page ID
	public function set_user_page_id(int $user_page_id){
		$this->user_page_id = $user_page_id;
	}

	public function has_user_page_id() : bool {
		return $this->user_page_id > -1;
	}

	public function get_user_page_id() : int {
		return $this->user_page_id;
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


	//Set and get visibility level
	public function set_visibility_level(int $visibility_level){
		$this->visibility_level = $visibility_level;
	}

	public function get_visibility_level() : int {
		return $this->visibility_level;
	}

	
	//Set and get the kind of post
	public function set_kind(string $kind){
		$this->kind = $kind == "" ? null : $kind;
	}

	public function has_kind() : bool {
		return $this->kind != null;
	}

	public function get_kind() : string {
		return $this->kind != null ? $this->kind : "null";
	}


	//Set and get the size of the file
	public function set_file_size(int $file_size){
		$this->file_size = $file_size;
	}

	public function has_file_size() : bool {
		return $this->file_size > -1;
	}

	public function get_file_size() : int {
		return $this->file_size;
	}


	//Set and get the type of the file
	public function set_file_type(string $file_type){
		$this->file_type = $file_type == "" ? null : $file_type;
	}

	public function has_file_type() : bool {
		return $this->file_type != null;
	}

	public function get_file_type() : string {
		return $this->file_type != null ? $this->file_type : "null";
	}


	//Set and get the path to the file
	public function set_file_path(string $file_path){
		$this->file_path = $file_path == "" ? null : $file_path;
	}

	public function has_file_path() : bool {
		return $this->file_path != null;
	}

	public function get_file_path() : string {
		return $this->file_path != null ? $this->file_path : "null";
	}


	//Set and get the path to the file as URL
	public function set_file_path_url(string $file_path_url){
		$this->file_path_url = $file_path_url == "" ? null : $file_path_url;
	}

	public function has_file_path_url() : bool {
		return $this->file_path_url != null;
	}

	public function get_file_path_url() : string {
		return $this->file_path_url != null ? $this->file_path_url : "null";
	}


	//Set and get the ID of the movie
	public function set_video_id(int $video_id){
		$this->video_id = $video_id;
	}

	public function has_video_id() : bool {
		return $this->video_id > -1;
	}

	public function get_video_id() : int {
		return $this->video_id;
	}


	//Set and get information about the associated movie
	public function set_movie(Movie $movie){
		$this->movie = $movie;
	}

	public function has_movie() : bool {
		return $this->movie != null;
	}

	public function get_movie() : Movie {
		return $this->movie;
	}


	//Set and get the end of the countdown timer
	public function set_time_end(int $time_end){
		$this->time_end = $time_end;
	}

	public function has_time_end() : bool {
		return $this->time_end > -1;
	}

	public function get_time_end() : int {
		return $this->time_end;
	}


	//Set and get the URL of the link
	public function set_link_url(string $link_url){
		$this->link_url = $link_url == "" ? null : $link_url;
	}

	public function has_link_url() : bool {
		return $this->link_url != null;
	}

	public function get_link_url() : string {
		return $this->link_url != null ? $this->link_url : "null";
	}


	//Set and get the title of the link
	public function set_link_title(string $link_title){
		$this->link_title = $link_title == "" ? null : $link_title;
	}

	public function has_link_title() : bool {
		return $this->link_title != null;
	}

	public function get_link_title() : string {
		return $this->link_title != null ? $this->link_title : "null";
	}


	//Set and get the description of the link
	public function set_link_description(string $link_description){
		$this->link_description = $link_description == "" ? null : $link_description;
	}

	public function has_link_description() : bool {
		return $this->link_description != null;
	}

	public function get_link_description() : string {
		return $this->link_description != null ? $this->link_description : "null";
	}


	//Set and get the image associated with the link
	public function set_link_image(string $link_image){
		$this->link_image = $link_image == "" ? null : $link_image;
	}

	public function has_link_image() : bool {
		return $this->link_image != null;
	}

	public function get_link_image() : string {
		return $this->link_image != null ? $this->link_image : "null";
	}


	//Set and get information about the associated survey
	public function set_survey(Survey $survey){
		$this->survey = $survey;
	}

	public function has_survey() : bool {
		return $this->survey != null;
	}

	public function get_survey() : Survey {
		return $this->survey;
	}


	//Set and get the number of likes of the post
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


	//Set and get the list of associated comments
	public function set_comments(array $comments){
		$this->comments = $comments;
	}

	public function has_comments() : bool {
		return $this->comments != null;
	}

	public function get_comments() : array {
		return $this->comments;
	}

	//Set and get user access level
	public function set_user_access_level(int $user_access_level){
		$this->user_access_level = $user_access_level;
	}

	public function get_user_access_level() : int {
		return $this->user_access_level;
	}
}