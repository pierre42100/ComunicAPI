<?php
/**
 * Post model
 * 
 * @author Pierre HUBERT
 */

class Post extends BaseUniqueObjectFromUser {

	//Private fields
	private $kind_page;
	private $kind_page_id = -1;
	private $content;
	private $visibility_level;
	private $kind;
	private $file_size = -1;
	private $file_type;
	private $file_path;
	private $file_path_url;
	private $movie_id = -1;
	private $movie;
	private $time_end = -1;
	private $link_url;
	private $link_title;
	private $link_description;
	private $link_image;
	private $survey;
	private $likes = -1;
	private $userLike;
	private $comments;
	private $has_comments = false;
	private $user_access;


	//Set and get the kind of page
	public function set_kind_page(string $kind_page){
		$this->kind_page = $kind_page == "" ? null : $kind_page;
	}

	public function has_kind_page() : bool {
		return $this->kind_page != null;
	}

	public function get_kind_page() : string {
		return $this->kind_page != null ? $this->kind_page : "null";
	}


	//Set and get the ID of the kind of page the posts belongs to
	public function set_kind_page_id(int $kind_page_id){
		$this->kind_page_id = $kind_page_id;
	}

	public function has_kind_page_id() : bool {
		return $this->kind_page_id > -1;
	}

	public function get_kind_page_id() : int {
		return $this->kind_page_id;
	}

	
	//Set and get the target page ID
	public function set_user_page_id(int $user_page_id){
		if($user_page_id > 0)
			$this->set_kind_page(Posts::PAGE_KIND_USER);
		$this->kind_page_id = $user_page_id;
	}

	public function has_user_page_id() : bool {
		return $this->kind_page_id > 0 && $this->kind_page == Posts::PAGE_KIND_USER;
	}

	public function get_user_page_id() : int {
		return $this->kind_page == Posts::PAGE_KIND_USER ? $this->kind_page_id : 0;
	}

	//Set and get the target group ID
	public function set_group_id(int $group_id){
		if($group_id > 0){
			$this->set_kind_page(Posts::PAGE_KIND_GROUP);
			$this->kind_page_id = $group_id;
		}
	}

	public function has_group_id() : bool {
		return $this->kind_page_id > 0 && $this->kind_page == Posts::PAGE_KIND_GROUP;
	}

	public function get_group_id() : int {
		return $this->kind_page == Posts::PAGE_KIND_GROUP ? $this->kind_page_id : 0;
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
	public function set_movie_id(int $movie_id){
		$this->movie_id = $movie_id;
	}

	public function has_movie_id() : bool {
		return $this->movie_id > 0;
	}

	public function get_movie_id() : int {
		return $this->movie_id;
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
		$this->has_comments = true;
	}

	public function has_comments() : bool {
		return $this->has_comments;
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