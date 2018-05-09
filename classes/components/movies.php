<?php
/**
 * Users personnal movies
 * 
 * @author Pierre HUBERT
 */

class Movies {

	/**
	 * Movies table name
	 */
	const MOVIES_TABLE = "galerie_video";

	/**
	 * Get the entire list of movies of a user
	 * 
	 * @param int $userID The ID of the user to get
	 * @return array The list of movie of the user
	 */
	public function get_list(int $userID) : array {

		//Perform a request on the database
		$conditions = "WHERE ID_user = ? ORDER BY ID DESC";
		$values = array($userID);
		
		$results = CS::get()->db->select($this::MOVIES_TABLE, $conditions, $values);

		//Process the list of movies
		return $this->processMultipleDBentry($results);
	}

	/**
	 * Get information about a movie
	 * 
	 * @param int $movieID The ID of the target movie
	 * @return Movie Information about the movie (empty in case of failure)
	 */
	public function get_info(int $movieID) : Movie {

		//Perform a request in the database
		$condition = "WHERE ID = ?";
		$condValues = array($movieID);
		$result = CS::get()->db->select($this::MOVIES_TABLE, $condition, $condValues);
		
		//Check if we got a response
		if(count($result) == 0)
			return array();

		return $this->dbToMovie($result[0]);

	}

	/**
	 * Check whether a movie specified by its ID exists or not
	 * 
	 * @param int $movieID The ID of the movie to check
	 * @return bool TRUE if the movie exists / false else
	 */
	public function exist(int $movieID) : bool {
		return CS::get()->db->count($this::MOVIES_TABLE, "WHERE ID = ?", array($movieID)) > 0;
	}

	/**
	 * Get the ID of the owner of a movie
	 * 
	 * @param int $movieID The ID of the target movie
	 * @return int The ID of the owner of the movie / 0 if none found
	 */
	public function get_owner(int $movieID) : int {

		//Get infos about the movie
		$movie = $this->get_info($movieID);

		if(!$movie->isValid())
			return 0;

		//Return the ID of the owner of the movie
		return $movie->get_userID();;

	}

	/**
	 * Delete a movie
	 * 
	 * @param Movie $movie The movie to delete
	 * @return bool TRUE for a success / FALSE else
	 */
	private function delete(Movie $movie) : bool {

		//Delete related posts
		if(!components()->posts->deleteAllWithMovie($movie->get_id()))
			return FALSE;

		//Delete file (if possible)
		if(!$movie->has_uri())
			return FAlSE;
		$file_path = path_user_data($movie->get_uri(), TRUE);

		if(file_exists($file_path)){
			if(!unlink($file_path))
				return FALSE;
		}

		//Remove database entry
		return CS::get()->db->deleteEntry(
			$this::MOVIES_TABLE, 
			"ID = ?", 
			array($movie->get_id()));
	}

	/**
	 * Delete (remove) all the movies of a user
	 * 
	 * @param int $userID The ID of the target user
	 * @return bool TRUE for success / FALSE else
	 */
	public function deleteAllUser(int $userID) : bool {

		//Get the list of movies of the user
		$list = $this->get_list($userID);

		//Process the list of movies
		foreach($list as $movie){
			if(!$this->delete($movie))
				return FALSE;
		}

		//Success
		return TRUE;
	}

	/**
	 * Process a list of database entries into Movie entries
	 * 
	 * @param array $entries The entries to parse
	 * @return array Generated list of entries
	 */
	private function processMultipleDBentry(array $entries) : array {
		$movies = array();
		foreach($entries as $row)
			$movies[] = $this->dbToMovie($row);

		return $movies;
	}

	/**
	 * Convert database entry into movie object
	 * 
	 * @param array $entry The database entry
	 * @return Movie Generated object
	 */
	private function dbToMovie(array $entry) : Movie {

		$movie = new Movie();

		$movie->set_id($entry["ID"]);
		$movie->set_uri($entry["URL"]);
		$movie->set_url(path_user_data($movie->get_uri()));
		$movie->set_userID($entry["ID_user"]);
		$movie->set_name($entry["nom_video"]);
		$movie->set_file_type($entry["file_type"]);
		$movie->set_size($entry["size"]);

		return $movie;
	}

}

//Register component
Components::register("movies", new Movies());