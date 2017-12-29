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

	/**
	 * Update like status
	 * 
	 * @param int $userID The ID of the user
	 * @param bool $like New like status
	 * @param int $id The ID of the component element to update
	 * @param string $kind The kind of component
	 */
	public function update(int $userID, bool $like, int $id, string $kind){

		//Check if the request is not to like anymore
		if(!$like){
			
			//Delete on the database
			$conditions = "ID_personne = ? AND ID_type = ? AND type = ?";
			$values = array($userID, $id, $this::KINDS_DB[$kind]);
			CS::get()->db->deleteEntry($this::LIKES_TABLE, $conditions, $values);

		}

		//We have to like component
		else {

			//Check if user is already liking component
			if($this->is_liking($userID, $id, $kind))
				return; //Nothing to be done
			
			//Insert in the database
			$values = array(
				"ID_type" => $id,
				"ID_personne" => $userID,
				"Date_envoi" => mysql_date(),
				"type" => $this::KINDS_DB[$kind]
			);

			//Insert in the database
			CS::get()->db->addLine($this::LIKES_TABLE, $values);

		}

	}

}

//Register class
Components::register("likes", new Likes());