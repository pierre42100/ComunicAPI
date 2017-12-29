<?php
/**
 * Likes class
 * 
 * @author Pierre HUBERT
 */

class Likes {

	/**
	 * Database table name
	 */
	const LIKES_TABLE = "aime";

	/**
	 * Kind of likes
	 */
	const LIKE_USER = "user";
	const LIKE_POST = "post";
	const LIKE_COMMENT = "comment";

	/**
	 * Translation of the kinds of like for the database
	 */
	const KINDS_DB = array(
		Likes::LIKE_USER => "page",
		Likes::LIKE_POST => "texte",
		Likes::LIKE_COMMENT => "commentaire"
	);

	/**
	 * Count the number of likes for a specific component
	 * 
	 * @param int $id The ID of the component to count
	 * @param string $kind The kind of the component
	 * @return int The number of likes of the components
	 */
	public function count(int $id, string $kind) : int {
		
		//Perform a database request
		return CS::get()->db->count(
			$this::LIKES_TABLE,
			"WHERE ID_type = ? AND type = ?",
			array($id, $this::KINDS_DB[$kind])
		);

	}

	/**
	 * Check whether user likes or not somethind
	 * 
	 * @param int $userID The ID of the user
	 * @param int $id The ID of the thing to check
	 * @param string $kind The kind of the thing to check
	 * @return bool True if the user likes / false else
	 */
	public function is_liking(int $userID, int $id, string $kind) : bool {

		//User "0" does not like content
		if($userID == 0)
			return false;
		
		//Perform check on database
		return CS::get()->db->count(
			$this::LIKES_TABLE,
			"WHERE ID_type = ? AND type = ? AND ID_personne = ?",
			array($id, $this::KINDS_DB[$kind], $userID)
		) == 1;

	}

}

//Register class
Components::register("likes", new Likes());