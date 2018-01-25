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
	 * @param bool $load_comments Specify whether the comments should be
	 * loaded or not (default to true)
	 * @return array The list of comments of the post
	 */
	public function get(int $postID, bool $load_comments = true) : array {

		//Perform a request on the database
		$conditions = "WHERE ID_texte = ? ORDER BY ID";
		$condValues = array($postID);

		//Fetch the messages on the database
		$result = CS::get()->db->select($this::COMMENTS_TABLE, $conditions, $condValues);
		
		//Process comments list
		$comments = array();

		foreach($result as $entry){
			$comments[] = $this->parse_comment($entry, $load_comments);
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
		
		//Get the list of comments for the post
		$comments = $this->get($postID, FALSE);

		foreach($comments as $comment){

			//Delete the comment
			if(!$this->process_delete($comment))
				return false;

		}

		//Success
		return true;
	}

	/**
	 * Delete a single comment
	 * 
	 * @param int $commentID The ID of the comment to delete
	 * @return bool TRUE for a success / FALSE else
	 */
	public function delete(int $commentID) : bool {
		
		//Get informations about the comment
		$commentInfos = $this->get_single($commentID, false);

		//Check for errors
		if(count($commentInfos) == 0)
			return false;
		
		//Process deletion
		return $this->process_delete($commentInfos);
	}

	/**
	 * Process comment deletion
	 * 
	 * @param array $commentInfos Informations about the comment to delete
	 * @return bool TRUE for a success / FALSE else
	 */
	private function process_delete(array $commentInfos) : bool {

		//Get comment ID
		$commentID = $commentInfos["ID"];

		//Check if an image is associated to the comment
		if(strlen($commentInfos['img_path']) > 2){

			$image_path = path_user_data($commentInfos['img_path'], true);

			//Delete the image if it exists
			if(file_exists($image_path))
				unlink($image_path);

		}

		//Delete the likes associated to the comments
		if(!components()->likes->delete_all($commentID, Likes::LIKE_COMMENT))
			return false;

		//Delete the comment
		if(!CS::get()->db->deleteEntry($this::COMMENTS_TABLE, "ID = ?", array($commentID)))
			return false;

		//Success
		return true;

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
	 * Check if a user is the owner of a comment or not
	 * 
	 * @param int $userID The ID of the user to check
	 * @param int $commentID The ID of the comment to check
	 * @return bool TRUE if the user is the owner of the post / FALSE else
	 */
	public function is_owner(int $userID, int $commentID) : bool {
		return CS::get()->db->count(
			$this::COMMENTS_TABLE, 
			"WHERE ID = ? AND ID_personne = ?", 
			array($commentID, $userID)
		) > 0;
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