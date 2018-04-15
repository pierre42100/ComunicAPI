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
	 */
	public function getUserPosts(int $userID, int $targetID, int $startPoint = 0, int $limit = 10) : array {

		//Check the value of limit (security)
		if($limit < 1){
			throw new Exception("The limit of the query must absolutly be positive !");
		}

		//Get user visibility level
		$visibilityLevel = $this->getUserVisibility($userID, $targetID);

		//Prepare the request on the database
		$conditions = "WHERE ID_personne = ? AND (";
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

		//Parse posts
		$posts = array();
		foreach($list as $post){
			$posts[] = $this->parse_post($post, $getComments);
		}

		return $posts;

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
		$friendsList = components()->friends->getList($userID);


		//Prepare the request on the database
		//Add the visibility level conditions
		$conditions = "WHERE niveau_visibilite <= ? AND (ID_personne = ?";
		$dataConds = array($visibilityLevel, $userID);

		//Process the list of friends of the user
		foreach($friendsList as $friend){
			$friendID = $friend->getFriendID();
			$conditions .= " OR ID_personne = ?";
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
			$posts[] = $this->parse_post($row, $allow_comments);
		}

		//Return the list of posts
		return $posts;
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
	 * @param array $post_infos Informations about the post
	 * @param int $userID The ID of the user to check
	 * @return int The access level over the post
	 */
	public function access_level_with_infos(array $post_infos, int $userID) : int {

		//Check if the user is the owner of the post
		if($post_infos['userID'] == $userID)
			return $this::FULL_ACCESS;

		//Check if the post was made on the user page
		if($post_infos["user_page_id"] == $userID)
			return $this::INTERMEDIATE_ACCESS;

		//Check if the post is private
		if($post_infos["visibility_level"] == $this::VISIBILITY_USER)
			return $this::NO_ACCESS;
		
		//Check if the post is for friends only
		if($post_infos["visibility_level"] == $this::VISIBILITY_FRIENDS){

			//Check if user is signed in
			if($userID == 0)
				return $this::NO_ACCESS;
			
			//Check if this user and the owner of the page are friends or not
			else if(!CS::get()->components->friends->are_friend($userID, $post_infos['user_page_id']))
				return $this::NO_ACCESS;
			
			else
				//User can access the post
				return $this::BASIC_ACCESS;
		}

		//Check if the post is public
		if($post_infos['visibility_level'] == $this::VISIBILITY_PUBLIC){

			//Check if the two personns are friend
			if($userID != 0){
				if(CS::get()->components->friends->are_friend($userID, $post_infos['user_page_id']))
					return $this::BASIC_ACCESS;
			}

			//Get user visibility level
			$visibilityLevel = CS::get()->components->user->getVisibility($post_infos['user_page_id']);

			//If the page is open, access is free
			if($visibilityLevel == UserComponent::USER_PAGE_OPEN)
				return $this::BASIC_ACCESS;
			
			//Else check if the user is signed in and the page is public 
			else if($userID != 0 AND $visibilityLevel == UserComponent::USER_PAGE_PUBLIC)
				return $this::BASIC_ACCESS;
			
			else
				return $this::NO_ACCESS;
		}
		
		//Not implemented
		return $this::NO_ACCESS;

	}

	/**
	 * Create a new post
	 * 
	 * @param string $kind_page The kind of target page
	 * @param int $kind_page_id The ID of the target page
	 * @param int $userID The ID of the user creating the post
	 * @param string $content The content of the post
	 * @param int $visibility The visibility of the post
	 * @param string $kind The kind of post
	 * @param int $file_size The size of the associated file (0 by default - no file)
	 * @param string $file_type The mime type of the associated file
	 * @param string $file_path The path in user data pointing on the file
	 * @param int $videoID The ID of the associated personnal user video (0 for no video)
	 * @param int $time_end The end of the countdown timer (if any)
	 * @param string $link_url The URL of the link associated with the post (if any)
	 * @param string $link_title The title of the link associated with the post (if any)
	 * @param string $link_description The description of the link associated with the post (if any)
	 * @param string $link_image The link pointing on the image associated with the post (if any)
	 * @param string $survey_question The question of the survey associated with the post (if any)
	 * @param array $survey_answers The answers of the survey associated with the post (if any)
	 * @return int The ID of the created post or -1 in case of failure
	 */
	public function create(string $kind_page, int $kind_page_id, int $userID, string $content, 
							int $visibility, string $kind, int $file_size = 0, 
							string $file_type = null, string $file_path = null, int $videoID = 0, 
							int $time_end = 0, string $link_url = null, string $link_title = null,
							string $link_description = null, string $link_image = null, string $survey_question = null,
							array $survey_answers = array()) : int {
		
		//Generate new post informations
		//Get the corresponding kind of post for the database
		$post_kind_db = array_flip($this::POSTS_DB_TYPES)[$kind];

		//Generate valid date form for time_end value
		if($time_end != 0){
			$date_end = date("d/m/Y", $time_end);
			$array_date_end = explode("/", $date_end);

			//Save the values
			$day_end = $array_date_end[0];
			$month_end = $array_date_end[1];
			$year_end = $array_date_end[2];
		}
		
		//Process user page posts
		if($kind_page == $this::PAGE_KIND_USER){
			
			//Determine who is creating the post
			$post_user_id = $kind_page_id;
			$post_friend_id = $kind_page_id == $userID ? 0 : $userID;

		}
		else {
			throw new Exception("Unsupported kind of page : ".$kind_page." !");
		}

		//Generate database entry
		$data = array(
			"ID_personne" => $post_user_id,
			"ID_amis" => $post_friend_id,
			"date_envoi" => mysql_date(),
			"texte" => $content,
			"niveau_visibilite" => $visibility,
			"type" => $post_kind_db,
			
			//Generic files informations
			"size" => $file_size != 0 ? $file_size : null,
			"file_type" => $file_type,
			"path" => $file_path,

			//Movie posts
			"idvideo" => $videoID != 0 ? $videoID : null,

			//Countdown timer
			"jour_fin" => isset($day_end) ? $day_end : null,
			"mois_fin" => isset($month_end) ? $month_end : null,
			"annee_fin" => isset($year_end) ? $year_end : null,

			//Weblink page
			"url_page" => $link_url,
			"titre_page" => $link_title,
			"description_page" => $link_description,
			"image_page" => $link_image
		);

		//Insert the post
		if(!CS::get()->db->addLine($this::TABLE_NAME, $data))
			return -1;

		//Get post ID
		$postID = CS::get()->db->getLastInsertedID();

		//Create the survey if required
		if($kind == $this::POST_KIND_SURVEY){
			CS::get()->components->survey->create($postID, $userID, $survey_question, $survey_answers);
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
		$post_infos = $this->get_single($postID);

		//Check if we didn't get informations about the post
		if(count($post_infos) == 0)
			return false;

		//Delete the likes associated to the post
		if(!components()->likes->delete_all($postID, Likes::LIKE_POST))
			return false;
		
		//Delete all the comments associated to the post
		if(!components()->comments->delete_all($postID))
			return false;

		//Delete the associated image or PDF (if any)
		if($post_infos['kind'] == $this::POST_KIND_IMAGE
		|| $post_infos['kind'] == $this::POST_KIND_PDF){

			//Determine file path
			$file_path = path_user_data($post_infos["file_path"], true);

			//Check if the file exists
			if(file_exists($file_path)){

				//Try to delete it
				if(!unlink($file_path))
					return false;
			}
		}

		//Delete the associated survey (if any)
		if($post_infos['kind'] == $this::POST_KIND_SURVEY){
			
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
	 * @return array Informations about the post / empty array
	 * if the post was not found
	 */
	public function get_single(int $postID, bool $load_comments = false) : array {

		//Perform a request on the database
		$conditions = "WHERE ID = ?";
		$values = array($postID);
		$result = CS::get()->db->select($this::TABLE_NAME, $conditions, $values);

		//Check if we got a response
		if(count($result) == 0)
			return array(); //Empty array = error
		
		//Return parsed response
		return $this->parse_post($result[0], $load_comments);

	}

	/**
	 * Parse a user post from the database into
	 * the standardized version of post structure
	 * 
	 * @param array $src Informations about the post from the database
	 * @param bool $load_comments Get comments, if required
	 * @return array Parsed informations about the post
	 */
	private function parse_post(array $src, bool $load_comments) : array {
		
		$info = array();

		//Specify post ID
		$info["ID"] = $src["ID"];

		//Determine user ID
		$info["userID"] = $src["ID_amis"] == 0 ? $src["ID_personne"] : $src["ID_amis"];

		//Determine user page ID
		$info["user_page_id"] = $src["ID_personne"];

		//Time when the message was sent
		$info["post_time"] = strtotime($src["date_envoi"]);

		//Content of the message
		$info["content"] = $src["texte"];

		//Visibility level
		$info["visibility_level"] = $src["niveau_visibilite"];

		//Determine the kind of post
		$info["kind"] = $this::POSTS_DB_TYPES[$src["type"]];

		//Document info
		$info["file_size"] = $src["size"];
		$info["file_type"] = $src["file_type"];
		$info["file_path"] = $src["path"];
		$info["file_path_url"] = $src["path"] == null ? null : path_user_data($src["path"], false);

		//Video specific
		$info["video_id"] = $src["idvideo"];

		//Get informations about the video
		if(!is_null($info['video_id'])){
			$info['video_infos'] = CS::get()->components->movies->get_infos($info['video_id']);
		}
		else {
			$info['video_infos'] = null;
		}

		//Countdown timer specific
		if($src['annee_fin'] != 0)
			$info["time_end"] = strtotime($src["annee_fin"]."/".$src['mois_fin']."/".$src["jour_fin"]);
		else
			$info["time_end"] = null;
		
		//Weblink specific
		$info["link_url"] = $src["url_page"];
		$info["link_title"] = $src["titre_page"];
		$info["link_description"] = $src["description_page"];
		$info["link_image"] = $src["image_page"];

		//Survey specific
		if($info['kind'] == "survey"){
			$info["data_survey"] = CS::get()->components->survey->get_infos($info['ID']);
		}
		else {
			$info["data_survey"] = null;
		}

		//Get informations about likes
		$info["likes"] = CS::get()->components->likes->count($info["ID"], Likes::LIKE_POST);
		$info["userlike"] = user_signed_in() ? CS::get()->components->likes->is_liking(userID, $info["ID"], Likes::LIKE_POST) : false;

		//Load comments, if required
		if($load_comments)
			$info["comments"] = CS::get()->components->comments->get($info["ID"]);
		else
			$info["comments"] = null;

		return $info;

	}
}

//Register component
Components::register("posts", new Posts());