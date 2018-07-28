<?php
/**
 * Search controller
 *
 * @author Pierre HUBERT
 */

class SearchController
{

	/**
	 * Search results kinds to API
	 */
	const SEARCH_RESULTS_KINDS = array(
		SearchResult::KIND_USER => "user",
		SearchResult::KIND_GROUP => "group"
	);

	/**
	 * Peform a research on the database
	 *
	 * @url POST /search/user
	 * @url POST /user/search
	 */
	public function search_user(){
		user_login_required();

		//Check if the query was specified with the request
		if(!isset($_POST['query']))
			Rest_fatal_error(400, "Please specify search terms");
		$query = $_POST['query'];

		//Check the query
		if(strlen($query) < 1)
			Rest_fatal_error(401, "Empty requests not allowed !");
		
		//Check for search limit
		$searchLimit = (isset($_POST['searchLimit']) ? toInt($_POST['searchLimit']) : 5);

		//Check the limit
		if($searchLimit < 1 || $searchLimit > 25)
			Rest_fatal_error(401, "Invalid search limit !");
		
		//Perform research on the database and return results
		$results = CS::get()->components->search->search_user($query, $searchLimit);
		if($results === false)
			Rest_fatal_error(500, "An error occured while trying to perform a research in user list !");
		
		//Return results
		return $results;
	}

	/**
	 * Peform a global search (search for groups + users)
	 * 
	 * @url POST /search/global
	 */
	public function searchGlobal(){
		user_login_required();

		//Get search query
		$query = postString("query", 1);

		//Set abitrary limit
		$limit = 10;

		$results = array();
		
		//First, search for groups
		foreach(components()->search->search_group($query, $limit) as $groupID)
			$results[] = new SearchResult(SearchResult::KIND_GROUP, $groupID);
		$limit -= count($results);

		//Then search for users
		foreach(components()->search->search_user($query, $limit) as $userID)
			$results[] = new SearchResult(SearchResult::KIND_USER, $userID);
		
		//Parse and return result
		return self::MultipleSearchResultToAPI($results);
	}

	/**
	 * Parse multiple SearchResult entry to API
	 * 
	 * @param array $list The list of SearchResults to parse
	 * @return array Generated array
	 */
	public static function MultipleSearchResultToAPI(array $list) : array {
		$data = array();
		foreach($list as $entry)
			$data[] = self::SearchResultToAPI($entry);
		return $data;
	}

	/**
	 * Turn a SearchResult object into API object
	 * 
	 * @param SearchResult $result The result to process
	 * @return array Generated entry
	 */
	public static function SearchResultToAPI(SearchResult $result) : array {
		$data = array();

		$data["kind"] = self::SEARCH_RESULTS_KINDS[$result->get_kind()];
		$data["id"] = $result->get_kind_id();

		return $data;
	}
}