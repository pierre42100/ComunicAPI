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
			$movies[] = $this->parse_db_infos($row);

		return $movies;
	}

	/**
	 * Get informations about a movie
	 * 
	 * @param int $movieID The ID of the target movie
	 * @return array Informations about the movie (empty in case of failure)
	 */
	public function get_infos(int $movieID) : array {

		//Perform a request in the database
		$condition = "WHERE ID = ?";
		$condValues = array($movieID);
		$result = CS::get()->db->select($this::MOVIES_TABLE, $condition, $condValues);
		
		//Check if we got a response
		if(count($result) == 0)
			return array();

		return $this->parse_db_infos($result[0]);

	}

	/**
	 * Parse a video informations
	 * 
	 * @param array $db_infos Informations about the movie from
	 * the database
	 * @return array Parsed informations about the video
	 */
	private function parse_db_infos(array $db_infos) : array {

		$infos = array();

		//Get informations
		$infos["id"] = $db_infos["ID"];
		$infos["uri"] = $db_infos["URL"];
		$infos["url"] = path_user_data($infos['uri']);
		$infos["userID"] = $db_infos["ID_user"];
		$infos["name"] = $db_infos["nom_video"];
		$infos["file_type"] = $db_infos["file_type"];
		$infos["size"] = $db_infos["size"];

		return $infos;

	}

}

//Register component
Components::register("movies", new Movies());