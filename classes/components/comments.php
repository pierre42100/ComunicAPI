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
	 * Create a comment
	 * 
	 * @param Comment $comment Information about the comment to create
	 * @return int The ID of the created comment or 0 in case of failure
	 */
	public function create(Comment $comment) : int {

		//Generate data set
		$data = array(
			"ID_texte" => $comment->get_postID(),
			"ID_personne" => $comment->get_userID(),
			"date_envoi" => mysql_date(),
			"time_insert" => time(),
			"commentaire" => $comment->has_content() ? $comment->get_content() : "",
			"image_commentaire" => $comment->has_img_path() ? "file:".$comment->get_img_path() : ""
		);

		//Insert it in the database
		if(!CS::get()->db->addLine($this::COMMENTS_TABLE, $data))
			return 0;
		
		//Get the ID of the last inserted comment and return it
		return CS::get()->db->getLastInsertedID();
	}

	/**
	 * Fetch the comments of a post
	 * 
	 * @param int $postID The ID of the post
	 * @param bool $load_likes Specify whether the likes should be
	 * loaded or not (default to true)
	 * @return array The list of comments of the post (as Comment objects)
	 */
	public function get(int $postID, bool $load_likes = true) : array {

		//Perform a request on the database
		$conditions = "WHERE ID_texte = ? ORDER BY ID";
		$condValues = array($postID);

		//Fetch the messages on the database
		$result = CS::get()->db->select($this::COMMENTS_TABLE, $conditions, $condValues);
		
		//Process comments list
		return $this->processMultipleResults($result, $load_likes);

	}

	/**
	 * Fetch the list of comments of a user
	 * 
	 * @param int $userID The ID of the target user
	 * @return array The list of comments of the user, as comment object
	 */
	public function getAllUser(int $userID) : array {

		//Perform a request over the database
		$conditions = "WHERE ID_personne = ? ORDER BY ID";
		$condValues = array($userID);

		//Fetch the messages on the database
		$result = CS::get()->db->select($this::COMMENTS_TABLE, $conditions, $condValues);

		//Process comments list
		return $this->processMultipleResults($result, FALSE);
	}

	/**
	 * Get a single comment informations
	 * 
	 * @param int $commentID The ID of the comment to get
	 * @param bool $include_likes Specify if likes has to be loaded
	 * @return Comment Information about the comment (invalid comment in case of failure)
	 */
	public function get_single(int $commentID, bool $include_likes = false) : Comment {

		//Perform a request on the database
		$conditions = "WHERE ID = ?";
		$values = array($commentID);

		//Fetch the comment on the database
		$result = CS::get()->db->select($this::COMMENTS_TABLE, $conditions, $values);
		
		//Check for results
		if(count($result) == 0)
			return new Comment();
		
		//Return result
		return $this->dbToComment($result[0], $include_likes);
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
		if(!$commentInfos->isValid())
			return false;
		
		//Process deletion
		return $this->process_delete($commentInfos);
	}

	/**
	 * Process comment deletion
	 * 
	 * @param Comment $commentInfos Informations about the comment to delete
	 * @return bool TRUE for a success / FALSE else
	 */
	private function process_delete(Comment $commentInfos) : bool {

		//Get comment ID
		$commentID = $commentInfos->get_id();

		//Check if an image is associated to the comment
		if($commentInfos->has_img_path()){

			$image_path = path_user_data($commentInfos->get_img_path(), true);

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
	 * Edit a comment content
	 * 
	 * @param int $commentID The ID of the comment to update
	 * @param string $content The new content for the comment
	 * @return bool TRUE for a success / FALSE else
	 */
	public function edit(int $commentID, string $content) : bool {

		//Perform a request on the database
		$newValues = array(
			"commentaire" => $content
		);

		//Try to perform request
		return CS::get()->db->updateDB(
			$this::COMMENTS_TABLE, 
			"ID = ?", 
			$newValues, 
			array($commentID));

	}

	/**
	 * Get the ID of the post associated to a comment
	 * 
	 * @param int $commentID The ID of the comment
	 * @return int The ID of the associated post / 0 in case of failure
	 */
	public function getAssociatedPost(int $commentID) : int {

		//Get a single comment informations
		$comment = $this->get_single($commentID);

		//Check if we have got the required information and return it
		return $comment->has_postID() ? $comment->get_postID() : 0;
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
	 * Delete all the comments of a user
	 * 
	 * @param int $userID The ID of the target user
	 * @return bool TRUE for a success / FALSE else
	 */
	public function deleteAllUser(int $userID) : bool {

		//Get all the comments of the user
		$comments = $this->getAllUser($userID);

		//Process the list of comments
		$success = true;
		foreach($comments as $comment)
			$success = $this->process_delete($comment) ? $success : false;

		//Return result
		return $success;
	}

	/**
	 * Turn multiple database entries into Comment objects
	 * 
	 * @param array $entries The database entries to process
	 * @param bool $load_likes Specify whether likes should be loaded
	 * or not
	 * @return array The list of generated Comment objects
	 */
	private function processMultipleResults(array $entries, bool $load_likes) : array {

		$comments = array();

		foreach($entries as $entry){
			$comments[] = $this->dbToComment($entry, $load_likes);
		}

		return $comments;
	}

	/**
	 * Turn a comment database entry into a Comment object
	 * 
	 * @param array $data Database entry
	 * @param bool $load_likes Specify if the likes have to be loaded or not
	 * @return Comment Generated comment object
	 */
	private function dbToComment(array $data, bool $load_likes = true) : Comment {

		$comment = new Comment();

		$comment->set_id($data["ID"]);
		$comment->set_userID($data["ID_personne"]);
		$comment->set_postID($data["ID_texte"]);
		$comment->set_time_sent($data["time_insert"] == null ? strtotime($data["date_envoi"]) : $data["time_insert"]);
		$comment->set_content($data["commentaire"]);
		
		//Check for image
		if($data["image_commentaire"] != ""){
			$comment->set_img_path(str_replace("file:", "", $data["image_commentaire"]));
			$comment->set_img_url(path_user_data($comment->get_img_path(), false));
		}

		if($load_likes){
			//Get informations about likes
			$comment->set_likes(CS::get()->components->likes->count($comment->get_id(), Likes::LIKE_COMMENT));
			$comment->set_userLike(user_signed_in() ? CS::get()->components->likes->is_liking(userID, $comment->get_id(), Likes::LIKE_COMMENT) : false);
		}

		return $comment;

	}
}

//Register class
Components::register("comments", new Comments());