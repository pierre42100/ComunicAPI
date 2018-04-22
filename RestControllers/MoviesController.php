<?php
/**
 * User personnal movies controller
 * 
 * @author Pierre HUBERT
 */

class MoviesController {
	
	/**
	 * Get the list of movies of the user
	 * 
	 * @url POST /movies/get_list
	 */
	public function get_movies_list(){

		user_login_required(); //Need login

		//Fetch the database
		$movies = CS::get()->components->movies->get_list(userID);

		//Process the list of movies
		foreach($movies as $num=>$movie)
			$movies[$num] = $this->MovieToAPI($movie);

		return $movies;

	}

	/**
	 * Turn movie object into API standard object
	 * 
	 * @param Movie $movie The movie object to convert
	 * @return array API information about the movie
	 */
	public static function MovieToAPI(Movie $movie) : array {

		$data = array();

		$data["id"] = $movie->get_id();
		$data["uri"] = $movie->get_uri();
		$data["url"] = $movie->get_url();
		$data["userID"] = $movie->get_userID();
		$data["name"] = $movie->get_name();
		$data["file_type"] = $movie->get_file_type();
		$data["size"] = $movie->get_size();

		return $data;
	}
}