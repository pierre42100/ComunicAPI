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

		return $info;
	}


}

//Register class
Components::register("comments", new Comments());