<?php
/**
 * Search controller
 *
 * @author Pierre HUBERT
 */

class userController
{
	/**
	 * Peform a research on the database
	 *
	 * @url POST /search/request
	 */
	public function searchDatabase(){
		//Check if the query was specified with the request
		if(!isset($_POST['query']))
			Rest_fatal_error(400, "Please specify search terms");
		
		//Check for search limit
		$seachLimit = (isset($_POST['searchLimit']) ? $_POST['searchLimit']*1 : 5);

		//Check the limit
		if($seachLimit < 1 || $seachLimit > 25)
			Rest_fatal_error(401, "Invalid search limit !");
		
		//Perform research on the database and return results
		
	}
}