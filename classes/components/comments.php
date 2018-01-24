<?php
/**
 * Comments component class
 * 
 * @author Pierre HUBERT
 */

class Comments {

	/**
	 * Comments table
	 */
	const COMMENTS_TABLE = "commentaires";

	/**
	 * Fetch the comments of a post
	 * 
	 * @param int $postID The ID of the post
	 * @return array The list of comments of the post
	 */
	public function get(int $postID) : array {

		//Perform a request on the database
		$conditions = "WHERE ID_texte = ? ORDER BY ID";
		$condValues = array($postID);

		//Fetch the messages on the database
		$result = CS::get()->db->select($this::COMMENTS_TABLE, $conditions, $condValues);
		
		//Process comments list
		$comments = array();

		foreach($result as $entry){
			$comments[] = $this->parse_comment($entry);
		}

		return $comments;

	}

	/**
	 * Get a single comment informations
	 * 
	 * @param int $commentID The ID of the comment to get
	 * @param bool $include_likes Specify if likes has to be loaded
	 * @return array Information about the comment
	 */
	public function get_single(int $commentID, bool $include_likes = false) : array {

		//Perform a request on the database
		$conditions = "WHERE ID = ?";
		$values = array($commentID);

		//Fetch the comment on the database
		$result = CS::get()->db->select($this::COMMENTS_TABLE, $conditions, $values);
		
		//Check for results
		if(count($result) == 0)
			return array();
		
		//Return result
		return $this->parse_comment($result[0], $include_likes);
	}

	/**
	 * Delete all the comments associated to a post
	 * 
	 * @param int $postID The ID of the target post
	 * @return bool TRUE in case of success / FALSE else
	 */
	public function delete_all(int $postID) : bool {
		
		//Perform the request on the database
		//return CS::get()->db->deleteEntry($this::COMMENTS_TABLE, "ID_texte = ?", array($postID));
		return false;
	}

	/**
	 * Check if a comment exists or not
	 * 
	 * @param int $commentID The ID of the comment to check
	 * @return bool
	 */
	public function exists(int $commentID) : bool {

		return CS::get()->db->count($this::COMMENTS_TABLE, "WHERE ID = ?", array($commentID)) > 0;

	}

	/**
	 * Get the ID of the post associated to a comment
	 * 
	 * @param int $commentID The ID of the comment
	 * @return int The ID of the associated post / 0 in case of failure
	 */
	public function getAssociatedPost(int $commentID) : int {

		//Get a single comment informations
		$commentInfos = $this->get_single($commentID);

		//Check if we have got the required information and return it
		return isset($commentInfos["postID"]) ? $commentInfos["postID"] : 0;
	}

	/**
	 * Parse a comment informations
	 * 
	 * @param array $src Informations from the database
	 * @param bool $load_likes Specify if the likes have to be loaded or not
	 * @return array Informations about the comments ready to be sent
	 */
	private function parse_comment(array $src, bool $load_likes = true): array {
		$info = array();

		$info["ID"] = $src["ID"];
		$info["userID"] = $src["ID_personne"];
		$info["postID"] = $src["ID_texte"];
		$info["time_sent"] = strtotime($src["date_envoi"]);
		$info["content"] = $src["commentaire"];

		//Check for image
		if($src["image_commentaire"] != ""){
			$info["img_path"] = str_replace("file:", "", $src["image_commentaire"]);
			$info["img_url"] = path_user_data($info["img_path"], false);

		} else {
			$info["img_path"] = null;
			$info["img_url"] = null;
		}

		if($load_likes){
			//Get informations about likes
			$info["likes"] = CS::get()->components->likes->count($info["ID"], Likes::LIKE_COMMENT);
			$info["userlike"] = user_signed_in() ? CS::get()->components->likes->is_liking(userID, $info["ID"], Likes::LIKE_COMMENT) : false;
		}

		return $info;
	}


}

//Register class
Components::register("comments", new Comments());