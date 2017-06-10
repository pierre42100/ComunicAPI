<?php
/**
 * Search controller
 *
 * @author Pierre HUBERT
 */

class searchController
{
	/**
	 * Peform a research on the database
	 *
	 * @url POST /search/request
	 * @url POST /user/search
	 */
	public function searchDatabase(){
		user_login_required();

		//Check if the query was specified with the request
		if(!isset($_POST['query']))
			Rest_fatal_error(400, "Please specify search terms");
		
		//Check for search limit
		$searchLimit = (isset($_POST['searchLimit']) ? $_POST['searchLimit']*1 : 5);

		//Check the limit
		if($searchLimit < 1 || $searchLimit > 25)
			Rest_fatal_error(401, "Invalid search limit !");
		
		//Perform research on the database and return results
		$results = CS::get()->components->searchUser->search($_POST['query'], $searchLimit);
		if($results === false)
			Rest_fatal_error(500, "An error occured while trying to perform a research in user list !");
		
		//Return results
		return $results;
	}
}