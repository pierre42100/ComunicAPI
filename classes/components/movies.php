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

		$movies = array();
		foreach($results as $row)
			$movies[] = $this->dbToMovie($row);

		return $movies;
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