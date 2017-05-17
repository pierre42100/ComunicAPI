<?php
/**
 * Sites informations controller
 *
 * @author Pierre HUBERT
 */

class sitesController{
	/**
	 * Get the informations about a website given a URL
	 *
	 * @url GET /site/$url/infos
	 * @url POST /site/infos
	 */
	public function getInfosURL($url=false){

		//We check if the URL was passed in $_POST mode
		if(!$url){
			if(!isset($_POST['url']))
				Rest_fatal_error(401, "Please specify an URL !");
			
			$url = $_POST['url'];
		}

		//We try to get informations about a websites using its URL
		if(!$infos = DW::get()->sites->getInfosFromURL($url))
			Rest_fatal_error(500, "Couldn't get informations about the URL !");
		
		//Return the informations
		return $infos;
	}

	/**
	 * Get the informations history about a website given a URL
	 *
	 * @url GET /site/$url/history
	 * @url POST /site/history
	 */
	public function getInfosURLHistory($url=false){

		//We check if the URL was passed in $_POST mode
		if(!$url){
			if(!isset($_POST['url']))
				Rest_fatal_error(401, "Please specify an URL !");
			
			$url = $_POST['url'];
		}

		//We try to get informations about a websites using its URL
		if(!$infos = DW::get()->sites->getInfosFromURL($url, 0))
			Rest_fatal_error(500, "Couldn't get history informations about the URL !");
		
		//Return the informations
		return $infos;
	}
}