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
	 * Delete all the comments associated to a post
	 * 
	 * @param int $postID The ID of the target post
	 * @return bool TRUE in case of success / FALSE else
	 */
	public function delete_all(int $postID) : bool {
		
		//Perform the request on the database
		return CS::get()->db->deleteEntry($this::COMMENTS_TABLE, "ID_texte = ?", array($postID));

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
	 * Parse a comment informations
	 * 
	 * @param array $src Informations from the database
	 * @return array Informations about the comments ready to be sent
	 */
	private function parse_comment(array $src): array {
		$info = array();

		$info["ID"] = $src["ID"];
		$info["userID"] = $src["ID_personne"];
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

		//Get informations about likes
		$info["likes"] = CS::get()->components->likes->count($info["ID"], Likes::LIKE_COMMENT);
		$info["userlike"] = user_signed_in() ? CS::get()->components->likes->is_liking(userID, $info["ID"], Likes::LIKE_COMMENT) : false;

		return $info;
	}


}

//Register class
Components::register("comments", new Comments());