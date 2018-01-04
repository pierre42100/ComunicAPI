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
	 * Table informations
	 */
	//Name of the table
	const TABLE_NAME = "texte";

	//Translation of posts types between the API language and the database structure
	const POSTS_TYPE = array(
		"texte" => "text",
		"image" => "image",
		"webpage_link" => "weblink",
		"pdf" => "pdf",
		"video" => "movie",
		"count_down" => "countdown",
		"sondage" => "survey",
		"youtube" => "youtube"
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
			if($visibilityLevel == User::USER_PAGE_OPEN)
				return $this::BASIC_ACCESS;
			
			//Else check if the user is signed in and the page is public 
			else if($userID != 0 AND $visibilityLevel == User::USER_PAGE_PUBLIC)
				return $this::BASIC_ACCESS;
			
			else
				return $this::NO_ACCESS;
		}
		
		//Not implemented
		return $this::NO_ACCESS;

	}

	/**
	 * Fetch a single post from the database
	 * 
	 * @param int $postID The ID of the post to get
	 * @return array Informations about the post / empty array
	 * if the post was not found
	 */
	private function get_single(int $postID) : array {

		//Perform a request on the database
		$conditions = "WHERE ID = ?";
		$values = array($postID);
		$result = CS::get()->db->select($this::TABLE_NAME, $conditions, $values);

		//Check if we got a response
		if(count($result) == 0)
			return array(); //Empty array = error
		
		//Return parsed response
		return $this->parse_post($result[0], false);

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
		$info["kind"] = $this::POSTS_TYPE[$src["type"]];

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