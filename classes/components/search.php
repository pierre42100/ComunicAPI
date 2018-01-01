<?php
/**
 * Search controller
 *
 * @author Pierre HUBERT
 */

class search {

	/**
	 * Search for user in the database
	 *
	 * @param string $query The query to research on the database
	 * @param int $limit The number of results to return on the screen (default: 10)
	 * @return array the result of the result
	 */
	public function search_user(string $query, int $limit = 10) : array{

		//Prepare query string
		$query = "%".str_replace(" ", "%", $query)."%";

		//Prepare a request on the database
		$tableName = "utilisateurs";
		$conditions = "WHERE (nom LIKE ?) || (prenom LIKE ?) || (CONCAT(prenom, '%', nom) LIKE ?) || (CONCAT(nom, '%', prenom) LIKE ?) LIMIT ".$limit*1;
		$datasCond = array($query, $query, $query, $query);
		$fields = array("ID");

		//Perform the request on the database
		$results = CS::get()->db->select($tableName, $conditions, $datasCond, $fields);

		//Prepare return
		$return = array();
		foreach($results as $value){
			$return[] = $value["ID"];
		}

		//Return result
		return $return;
	}
}

//Register class
Components::register("search", new search());