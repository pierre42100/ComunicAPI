<?php
/**
 * Posts management class
 * 
 * @author Pierre HUBERT
 */

class Posts {

	/**
	 * Visibility levels
	 */
	//Posts that can be seen by anyone
	const VISIBILITY_PUBLIC = 1;

	//Posts that can be seen by the friends of the user
	const VISIBILITY_FRIENDS = 2;

	//Posts that can be seen by the user only
	const VISIBILITY_USER = 3;

	//Posts that can be seen by the members of a group (same as friends)
	const VISIBILITY_GROUP_MEMBERS = 50;

	/**
	 * Access level to a post
	 */
	//When a user can't access to a post
	const NO_ACCESS = 0;

	//When a user can see a post and perform basic actions such as liking
	const BASIC_ACCESS = 1;

	//When a user has intermediate access to the post (delete post)
	const INTERMEDIATE_ACCESS = 2;

	//When a user has a full access to the post
	const FULL_ACCESS = 3;

	/**
	 * Kinds of posts page
	 */
	//Post on user page
	const PAGE_KIND_USER = "user";
	const PAGE_KIND_GROUP = "group";

	/**
	 * Kinds of post
	 */
	const POST_KIND_TEXT = "text";
	const POST_KIND_IMAGE = "image";
	const POST_KIND_WEBLINK = "weblink";
	const POST_KIND_PDF = "pdf";
	const POST_KIND_MOVIE = "movie";
	const POST_KIND_COUNTDOWN = "countdown";
	const POST_KIND_SURVEY = "survey";
	const POST_KIND_YOUTUBE = "youtube";

	/**
	 * Table informations
	 */
	//Name of the table
	const TABLE_NAME = "texte";

	//Translation of posts types between the API language and the database structure
	const POSTS_DB_TYPES = array(
		"texte" => Posts::POST_KIND_TEXT,
		"image" => Posts::POST_KIND_IMAGE,
		"webpage_link" => Posts::POST_KIND_WEBLINK,
		"pdf" => Posts::POST_KIND_PDF,
		"video" => Posts::POST_KIND_MOVIE,
		"count_down" => Posts::POST_KIND_COUNTDOWN,
		"sondage" => Posts::POST_KIND_SURVEY,
		"youtube" => Posts::POST_KIND_YOUTUBE
	);

	/**
	 * Get the visibility level of a user other another user
	 * 
	 * @param int $userID The ID of the user to fetch
	 * @param int $targetID The ID of the user target
	 * @return int Visibility level
	 */
	public function getUserVisibility(int $userID, int $targetID) : int {

		//If the user making the request and the target user are the same
		if($userID == $targetID)
			return $this::VISIBILITY_USER;
		
		//Check user if is signed out
		if($userID == 0)
			return $this::VISIBILITY_PUBLIC;
		
		//Check if the two users are friends or not
		if(CS::get()->components->friends->are_friend($userID, $targetID))
			//Users are friends
			return $this::VISIBILITY_FRIENDS;

		else
			//Users are not friend
			return $this::VISIBILITY_PUBLIC;

	}

	/**
	 * Get a list of post of a user
	 * 
	 * @param int $userID The ID of the user making the request
	 * @param int $targetID The ID of the target user
	 * @param int $startPoint The startpoint for the request (0 stands for none)
	 * @param int $limit The maximum number of messages to fetch
	 * @return array The list of posts of the user
	 */
	public function getUserPosts(int $userID, int $targetID, int $startPoint = 0, int $limit = 10) : array {

		//Check the value of limit (security)
		if($limit < 1){
			throw new Exception("The limit of the query must absolutly be positive !");
		}

		//Get user visibility level
		$visibilityLevel = $this->getUserVisibility($userID, $targetID);

		//Prepare the request on the database
		$conditions = "WHERE ID_personne = ? AND group_id = 0 AND (";
		$dataConds = array($targetID);

		//Add the visibility level conditions
		$conditions .= "(niveau_visibilite <= ?)";
		$dataConds[] = $visibilityLevel;

		//If user is signed in, include all the posts that he has created
		if($userID > 0){
			$conditions .= " OR (ID_amis = ?) ";
			$dataConds[] = $userID;
		}

		//Close permissions conditions
		$conditions .= ")";
		
		//Add startpoint condition if required (and get older messages)
		if($startPoint != 0){
			$conditions .= " AND ID <= ? ";
			$dataConds[] = $startPoint;
		}

		//Specify order and limit
		$conditions.= " ORDER BY ID DESC LIMIT ".$limit;

		//Perform the request
		$list = CS::get()->db->select(
			$this::TABLE_NAME,
			$conditions,
			$dataConds
		);

		//Check if the user allow comments on his page or not
		$getComments = CS::get()->components->user->allowComments($targetID);

		//Parse and return posts
		return $this->processGetMultiple($list, $getComments);

	}

	/**
	 * Get the posts of a group
	 * 
	 * @param int $groupID The ID of the related group
	 * @param bool $all_posts Specify whether we should get all the posts of the user or not
	 * @param int $from Start point for the query
	 * @param int $limit The limit for the request (default = 10)
	 */
	public function getGroupPosts(int $groupID, bool $all_posts, int $from = 0, int $limit = 10){

		//Check the value of limit (security)
		if($limit < 1){
			throw new Exception("The limit of the query must absolutly be positive !");
		}

		//Get user visibility level
		$visibilityLevel = $all_posts ? $this::VISIBILITY_GROUP_MEMBERS : $this::VISIBILITY_PUBLIC;

		//Prepare the request on the database
		$conditions = "WHERE group_id = ? AND (";
		$dataConds = array($groupID);

		//Add the visibility level conditions
		$conditions .= "(niveau_visibilite <= ?)";
		$dataConds[] = $visibilityLevel;

		//Close permissions conditions
		$conditions .= ")";
		
		//Add startpoint condition if required (and get older messages)
		if($from != 0){
			$conditions .= " AND ID <= ? ";
			$dataConds[] = $from;
		}

		//Specify order and limit
		$conditions.= " ORDER BY ID DESC LIMIT ".$limit;

		//Perform the request
		$list = CS::get()->db->select(
			$this::TABLE_NAME,
			$conditions,
			$dataConds
		);

		//Parse and return posts
		return $this->processGetMultiple($list, TRUE);

	}

	/**
	 * Get the list of latest posts for a user
	 * 
	 * @param int $userID The ID of the user requesting its list of posts
	 * @param int $startPoint The startpoint of the research (default: 0 = none)
	 * @param int $limit The limit of the research (default: 10)
	 * @return array The list of newest posts for the user
	 */
	public function get_latest(int $userID, int $startPoint = 0, int $limit = 10) : array {

		//Check the value of limit (security)
		if($limit < 1){
			throw new Exception("The limit of the query must absolutly be positive !");
		}

		//Arbitrary visibility level
		$visibilityLevel = self::VISIBILITY_FRIENDS;

		//Get the list of friends of the user
		$friendsList = components()->friends->getList($userID, -1, TRUE);


		//Prepare the request on the database
		//Add the visibility level conditions
		$conditions = "WHERE group_id = 0 AND niveau_visibilite <= ? AND (ID_personne = ?";
		$dataConds = array($visibilityLevel, $userID);

		//Process the list of friends of the user
		foreach($friendsList as $friend){
			$friendID = $friend->getFriendID();
			$conditions .= " OR (ID_personne = ?)";
			$dataConds[] = $friendID;
		}

		//Close user list conditions
		$conditions .= ")";
		
		//Add startpoint condition if required (and get older messages)
		if($startPoint != 0){
			$conditions .= " AND ID <= ? ";
			$dataConds[] = $startPoint;
		}

		//Specify order and limit
		$conditions.= " ORDER BY ID DESC LIMIT ".$limit;

		//Perform the request
		$list = CS::get()->db->select(
			$this::TABLE_NAME,
			$conditions,
			$dataConds
		);

		//Parse the list of posts
		$posts = array();
		foreach($list as $row){
			
			//Check if the user allow the comments
			$allow_comments = CS::get()->components->user->allowComments($row["ID_personne"]);

			//Parse post informations
			$posts[] = $this->dbToPost($row, $allow_comments);
		}

		//Return the list of posts
		return $posts;
	}

	/**
	 * Get the entire list of posts of a user, including posts made by the
	 * user on other page plus the posts of his friend on his page
	 * 
	 * This function does not take care of visibility level, and does not admit
	 * any kind of limit
	 * 
	 * @param int $userID The ID of the target user
	 * @param bool $load_comments Specify whether the comments should be also loaded
	 * or not (default: FALSE)
	 * @return array The list of posts
	 */
	public function getUserEntirePostsList(int $userID, bool $load_comments = FALSE) : array {

		//Prepare database request
		$conditions = "WHERE ID_personne = ? OR ID_amis = ?";
		$dataConds = array($userID, $userID);

		//Perform the request
		$list = CS::get()->db->select(
			$this::TABLE_NAME,
			$conditions,
			$dataConds
		);

		//Parse and return posts (do not load comments)
		return $this->processGetMultiple($list, $load_comments);
		
	}

	/**
	 * Get the entire list of posts that uses a movie
	 * 
	 * This function does not take care of visibility level, and does not admit
	 * any kind of limit
	 * 
	 * @param int $movieID The ID of the target movie
	 * @return array The list of posts
	 */
	public function getPostsForMovie(int $movieID) : array {

		//Security feature
		if($movieID < 1)
			return array();

		//Prepare database request
		$conditions = "WHERE idvideo = ?";
		$dataConds = array($movieID);

		//Perform the request
		$list = CS::get()->db->select(
			$this::TABLE_NAME,
			$conditions,
			$dataConds
		);

		//Parse and return posts (do not load comments)
		return $this->processGetMultiple($list, FALSE);
		
	}

	/**
	 * Check whether a post exists or not
	 * 
	 * @param int $postID The ID of the post to check
	 * @return bool TRUE if the post exists / FALSE else
	 */
	public function exist(int $postID) : bool {

		//Perform a request on the database
		return CS::get()->db->count($this::TABLE_NAME, "WHERE ID = ?", array($postID)) != 0;

	}

	/**
	 * Get the access level of a user about a post
	 * 
	 * @param int $postID The ID of the post to get
	 * @param int $userID The ID of the user to check
	 * @return int The access level over the post
	 */
	public function access_level(int $postID, int $userID) : int {
		
		//Get informations about the post
		$post_infos = $this->get_single($postID);

		return $this->access_level_with_infos($post_infos, $userID);
	}

	/**
	 * Get the access level of a user about a post
	 * 
	 * This function requires the informations about the post
	 * 
	 * @param Post $post_info Informations about the post
	 * @param int $userID The ID of the user to check
	 * @return int The access level over the post
	 */
	public function access_level_with_infos(Post $post_info, int $userID) : int {

		//Check the validity of the Post object
		if(!$post_info->isValid())
			return $this::NO_ACCESS;

		//Check if the user is the owner of the post
		if($post_info->get_userID() == $userID)
			return $this::FULL_ACCESS;
		
		//Special checks if the posts belongs to a user's page
		if($post_info->get_kind_page() == Posts::PAGE_KIND_USER){

			//Check if the post was made on the user page
			if($post_info->get_user_page_id() == $userID)
				return $this::INTERMEDIATE_ACCESS;

			//Check if the post is private
			if($post_info->get_visibility_level() == $this::VISIBILITY_USER)
				return $this::NO_ACCESS;
			
			//Check if the post is for friends only
			if($post_info->get_visibility_level() == $this::VISIBILITY_FRIENDS){

				//Check if user is signed in
				if($userID == 0)
					return $this::NO_ACCESS;
				
				//Check if this user and the owner of the page are friends or not
				else if(!CS::get()->components->friends->are_friend($userID, $post_info->get_user_page_id()))
					return $this::NO_ACCESS;
				
				else
					//User can access the post
					return $this::BASIC_ACCESS;
			}

			//Check if the post is public
			if($post_info->get_visibility_level() == $this::VISIBILITY_PUBLIC){

				//Check if the two personns are friend
				if($userID != 0){
					if(CS::get()->components->friends->are_friend($userID, $post_info->get_user_page_id()))
						return $this::BASIC_ACCESS;
				}

				//Get user visibility level
				$visibilityLevel = CS::get()->components->user->getVisibility($post_info->get_user_page_id());

				//If the page is open, access is free
				if($visibilityLevel == UserComponent::USER_PAGE_OPEN)
					return $this::BASIC_ACCESS;
				
				//Else check if the user is signed in and the page is public 
				else if($userID != 0 AND $visibilityLevel == UserComponent::USER_PAGE_PUBLIC)
					return $this::BASIC_ACCESS;
				
				else
					return $this::NO_ACCESS;
			}
		}

		//Checks if the posts belongs to a group's page
		if($post_info->get_kind_page() == Posts::PAGE_KIND_GROUP){

			//Get the access level of the user over the group
			$access_level = components()->groups->getMembershipLevel($userID, $post_info->get_group_id());

			//Moderators and administrators can delete all the posts of the group
			if($access_level < GroupMember::MEMBER)
				return $this::INTERMEDIATE_ACCESS;
				
			//Members of a group can see all the posts of the group
			if($access_level == GroupMember::MEMBER)
				return $this::BASIC_ACCESS;
				
			//Check if the post is public or not
			if($post_info->get_visibility_level() != Posts::VISIBILITY_PUBLIC)
				return $this::NO_ACCESS;
			
			//Check if the group is open or not
			if(!components()->groups->isOpen($post_info->get_group_id()))
				return $this::NO_ACCESS;
			
			// Post public + open group > basic access
			return $this::BASIC_ACCESS;
		}
		
		//Not implemented
		return $this::NO_ACCESS;

	}

	/**
	 * Create a new post
	 * 
	 * @param Post $post Information about the post to create
	 * @return int The ID of the created post or -1 in case of failure
	 */
	public function create(Post $post) : int {
		
		//Generate new post informations
		//Get the corresponding kind of post for the database
		$post_kind_db = array_flip($this::POSTS_DB_TYPES)[$post->get_kind()];

		//Generate valid date form for time_end value
		if($post->get_time_end() != 0){
			$date_end = date("d/m/Y", $post->get_time_end());
			$array_date_end = explode("/", $date_end);

			//Save the values
			$day_end = $array_date_end[0];
			$month_end = $array_date_end[1];
			$year_end = $array_date_end[2];
			$time_end = $post->get_time_end();
		}
		
		//Process user page posts
		if($post->get_kind_page() == $this::PAGE_KIND_USER){
			
			//Determine who is creating the post
			$post_user_id = $post->get_kind_page_id();
			$post_friend_id = $post->get_kind_page_id() == $post->get_userID() ? 0 : $post->get_userID();
			$post_group_id = 0;

		}
		else if($post->get_kind_page() == $this::PAGE_KIND_GROUP){

			$post_user_id = $post->get_userID();
			$post_friend_id = 0;
			$post_group_id = $post->get_kind_page_id();

		}
		else {
			throw new Exception("Unsupported kind of page : ".$post->get_kind_page()." !");
		}

		//Generate database entry
		$data = array(
			"ID_personne" => $post_user_id,
			"ID_amis" => $post_friend_id,
			"group_id" => $post_group_id,
			"date_envoi" => mysql_date(),
			"time_insert" => time(),
			"texte" => $post->has_content() ? $post->get_content() : "",
			"niveau_visibilite" => $post->get_visibility_level(),
			"type" => $post_kind_db,
			
			//Generic files informations
			"size" => $post->has_file_size() ? $post->get_file_size() : null,
			"file_type" => $post->has_file_type() ? $post->get_file_type() : null,
			"path" => $post->has_file_path() ? $post->get_file_path() : null,

			//Movie posts
			"idvideo" => $post->has_movie_id() ? $post->get_movie_id() : null,

			//Countdown timer
			"jour_fin" => isset($day_end) ? $day_end : null,
			"mois_fin" => isset($month_end) ? $month_end : null,
			"annee_fin" => isset($year_end) ? $year_end : null,
			"time_end" => isset($time_end) ? $time_end : null,

			//Weblink page
			"url_page" => $post->has_link_url() ? $post->get_link_url() : null,
			"titre_page" => $post->has_link_title() ? $post->get_link_title() : null,
			"description_page" => $post->has_link_description() ? $post->get_link_description() : null,
			"image_page" => $post->has_link_image() ? $post->get_link_image() : null
		);

		//Insert the post
		if(!CS::get()->db->addLine($this::TABLE_NAME, $data))
			return -1;

		//Get post ID
		$postID = CS::get()->db->getLastInsertedID();

		//Create the survey if required
		if($post->get_kind() == $this::POST_KIND_SURVEY){
			//Complete the request
			$survey = $post->get_survey();
			$survey->set_postID($postID);
			$survey->set_userID($post->get_userID());
			components()->survey->create($survey);
		}

		return $postID;
	}

	/**
	 * Update the visibility level of a post
	 * 
	 * @param int $postID The ID of the post to update
	 * @param int $level The new level for the post
	 * @return bool TRUE in case of success / FALSE in case of failure
	 */
	public function update_level(int $postID, int $level) : bool {

		//Set the new values
		$new_values = array(
			"niveau_visibilite" => $level
		);

		//Perform update
		return $this->perform_update($postID, $new_values);
	}

	/**
	 * Update the content of a post
	 * 
	 * @param int $postID The ID of the post to update
	 * @param string $new_content The new content for the post
	 * @return bool TRUE in case of success / FALSE else
	 */
	public function update_content(int $postID, string $new_content) : bool {
		
		//Make a request on the database
		$new_values = array(
			"texte" => $new_content
		);

		//Perform update
		return $this->perform_update($postID, $new_values);
	}

	/**
	 * Perform the update of some informations of a post
	 * 
	 * @param int $postID The ID of the post to update
	 * @param array $new_values The informations to change in the post
	 * @return bool TRUE for a success / FALSE for a failure 
	 */
	private function perform_update(int $postID, array $new_values) : bool {

		//Set the conditions
		$conditions = "ID = ?";
		$condValues = array($postID);

		//Perform the request
		return CS::get()->db->updateDB($this::TABLE_NAME, $conditions, $new_values, $condValues);
	}

	/**
	 * Delete a post
	 * 
	 * @param int $postID The ID of the post to delete
	 * @return bool TRUE for a success / FALSE for a failure
	 */
	public function delete(int $postID) : bool {

		//Get informations about the post
		$post_info = $this->get_single($postID);

		//Check if we didn't get informations about the post
		if(!$post_info->isValid())
			return false;

		//Delete the likes associated to the post
		if(!components()->likes->delete_all($postID, Likes::LIKE_POST))
			return false;
		
		//Delete all the comments associated to the post
		if(!components()->comments->delete_all($postID))
			return false;

		//Delete the associated image or PDF (if any)
		if($post_info->get_kind() == $this::POST_KIND_IMAGE
		|| $post_info->get_kind() == $this::POST_KIND_PDF){

			//Determine file path
			$file_path = path_user_data($post_info->get_file_path(), true);

			//Check if the file exists
			if(file_exists($file_path)){

				//Try to delete it
				if(!unlink($file_path))
					return false;
			}
		}

		//Delete the associated survey (if any)
		if($post_info->get_kind() == $this::POST_KIND_SURVEY){
			
			//Check the post has an associated survey
			if(components()->survey->exists($postID)){

				//Try to delete survey
				if(!components()->survey->delete($postID))
					return false;
			}

		}

		//Delete the post
		return CS::get()->db->deleteEntry($this::TABLE_NAME, "ID = ?", array($postID));
	}

	/**
	 * Fetch a single post from the database
	 * 
	 * @param int $postID The ID of the post to get
	 * @param bool $load_comments Specify if the comments should be loaded or not
	 * (no by default)
	 * @return Post Information about the post / invalid Post object
	 * if the post was not found
	 */
	public function get_single(int $postID, bool $load_comments = false) : Post {

		//Perform a request on the database
		$conditions = "WHERE ID = ?";
		$values = array($postID);
		$result = CS::get()->db->select($this::TABLE_NAME, $conditions, $values);

		//Check if we got a response
		if(count($result) == 0)
			return new Post(); //Empty array = error
		
		//Return parsed response
		return $this->dbToPost($result[0], $load_comments);

	}

	/**
	 * Delete all the posts of a user
	 * 
	 * @param int $userID The ID of the target user
	 * @return bool TRUE for a success / FALSE else
	 */
	public function deleteAllUser(int $userID) : bool {

		//Get the list of posts of the user
		$posts = $this->getUserEntirePostsList($userID);

		//Delete the list of posts
		foreach($posts as $post){

			//Delete the posts
			if(!$this->delete($post->get_id()))
				return FALSE;

		}

		//Success
		return TRUE;
	}

	/**
	 * Delete all the posts using a specified movie
	 * 
	 * @param int $movieID The ID of the target movie
	 * @return bool TRUE for a success / FALSE else
	 */
	public function deleteAllWithMovie(int $movieID) : bool {

		//Get the list of posts of the user
		$posts = $this->getPostsForMovie($movieID);

		//Delete the list of posts
		foreach($posts as $post){

			//Delete the posts
			if(!$this->delete($post->get_id()))
				return FALSE;

		}

		//Success
		return TRUE;
	}

	/**
	 * Process processing of multiples posts entries in database
	 * 
	 * @param array $entries Entries in the database
	 * @param bool $load_comments Specify whether the comments should
	 * be loaded or not
	 * @return array The list of parsed posts
	 */
	private function processGetMultiple(array $entries, bool $load_comments) : array {

		//Parse posts
		$posts = array();
		foreach($entries as $post){
			$posts[] = $this->dbToPost($post, $load_comments);
		}

		return $posts;
	}

	/**
	 * Turn a database entry into a Post object
	 * 
	 * @param array $entry The database entry to parse
	 * @param bool $load_comments Load the comments, if required
	 * @return Post Generated Post object
	 */
	private function dbToPost(array $entry, bool $load_comments) : Post {

		$post = new Post();

		//General information
		$post->set_id($entry["ID"]);
		$post->set_userID($entry["ID_amis"] == 0 ? $entry["ID_personne"] : $entry["ID_amis"]);

		//Determine the kind of target page and its ID
		$post->set_user_page_id($entry["ID_personne"]);
		$post->set_group_id($entry["group_id"]);
		
		$post->set_time_sent($entry["time_insert"] == null ? strtotime($entry["date_envoi"]) : $entry["time_insert"]);
		$post->set_content($entry["texte"]);
		$post->set_visibility_level($entry["niveau_visibilite"]);
		$post->set_kind($this::POSTS_DB_TYPES[$entry["type"]]);
		
		//File specific
		$post->set_file_size($entry["size"] != null ? $entry["size"] : -1);
		$post->set_file_type($entry["file_type"] != null ? $entry["file_type"] : "");
		$post->set_file_path($entry["path"] != null ? $entry["path"] : "");
		$post->set_file_path_url($post->has_file_path() ? path_user_data($post->get_file_path()) : "");
		
		//Movie specific
		$post->set_movie_id($entry["idvideo"] == null ? -1 : $entry["idvideo"]);
		if($post->has_movie_id())
			$post->set_movie(components()->movies->get_info($post->get_movie_id()));
		
	
		//Countdown timer - specific
		if($entry['annee_fin'] != 0)
			$post->set_time_end(strtotime($entry["annee_fin"]."/".$entry['mois_fin']."/".$entry["jour_fin"]));
		if($entry["time_end"] != 0)
			$post->set_time_end($entry["time_end"]);	
		
		//Web link
		$post->set_link_url($entry["url_page"] != null ? $entry["url_page"] : "");
		$post->set_link_title($entry["titre_page"] != null ? $entry["titre_page"] : "");
		$post->set_link_description($entry["description_page"] != null ? $entry["description_page"] : "");
		$post->set_link_image($entry["image_page"] != null ? $entry["image_page"] : "");


		//Survey specific
		if($post->get_kind() == "survey")
			$post->set_survey(components()->survey->get_infos($post->get_id()));
		
		
		//Get information about likes
		$post->set_likes(components()->likes->count($post->get_id(), Likes::LIKE_POST));
		$post->set_userLike(user_signed_in() ? components()->likes->is_liking(userID, $post->get_id(), Likes::LIKE_POST) : false);

		//Load comments, if requested
		if($load_comments)
			$post->set_comments(components()->comments->get($post->get_id()));

		return $post;

	}
}

//Register component
Components::register("posts", new Posts());