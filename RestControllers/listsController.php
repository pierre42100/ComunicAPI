<?php
/**
 * Lists management controller
 *
 * @author Pierre HUBERT
 */

class listsController {

	/**
	 * Get the complete list
	 *
	 * @url GET /list/get
	 * @url GET /list/get/
	 * @url GET /list/get/$time
	 */
	public function getList($time="current"){
		
		//We check if we want the current list or another one
		if($time === "current"){
			//Try to get the current list
			if(!$list = DW::get()->lists->getCurrent())
				Rest_fatal_error(500, "Couldn't get current list !");
		}
		else {
			//Get the list of the specified timestamp
			if(!$list = DW::get()->lists->getOnTimestamp($time*1))
				Rest_fatal_error(500, "Couldn't get the list on specified timestamp !");
		}

		//Return the list
		return $list;
	}
	

	/**
	 * Update the current list
	 *
	 * @url POST /list/update
	 */
	public function updateList(){
		
		//Authentication required (protected method)
		if(!DW::get()->auth->restAuth())
			Rest_fatal_error(401, "Authentication required !");
		
		//Try to update list
		if(!DW::get()->lists->update())
			Rest_fatal_error(500, "Couldn't update Decodex list !");
		
		//Else it is a success
		return array("success" => "This list was successfully updated !");
	}

	/**
	 * Get the list of available websites using urls
	 *
	 * @url GET /list/urls
	 */
	public function getListSites(){
		//We try to get the list of urls
		if(!$list = DW::get()->lists->getListUrls())
			Rest_fatal_error(500, "Couldn't get the list of urls !");
		
		//Return the list
		return $list;
	}

	/**
	 * Get the list of URLs only
	 *
	 * @url GET /list/urls/only
	 */
	public function getURLsOnly(){
		//We try to get the list of urls
		if(!$list = DW::get()->lists->getListUrls(true))
			Rest_fatal_error(500, "Couldn't get the list of urls !");
		
		//Return the list
		return $list;
	}
}