<?php
/**
 * User personnal movies controller
 * 
 * @author Pierre HUBERT
 */

class moviesController {
	
	/**
	 * Get the list of movies of the user
	 * 
	 * @url POST /movies/get_list
	 */
	public function get_movies_list(){

		user_login_required(); //Need login

		//Fetch the database
		$movies = CS::get()->components->movies->get_list(userID);

		return $movies;

	}

}