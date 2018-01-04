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