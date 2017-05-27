<?php
/**
 * Search user controller
 *
 * @author Pierre HUBERT
 */

class searchUser {

	/**
	 * Search for user in the database
	 *
	 * @param Sting $query The query to research on the database
	 * @param Integer $limit The number of results to return on the screen
	 * @return Array the result of the result
	 */
	public function search($query, $limit){
		return array(1, 2);
	}
}

//Register class
Components::register("searchUser", new searchUser());