<?php
/**
 * RestWelcome controller
 *
 * @author Pierre HUBERT
 */

class welcomeController {

	/**
	 * Returns informations about the API
	 *
	 * @url GET /
	 * @url GET /infos
	 */
	public function getInfos(){
		return array(
			"serviceDescription" => "This service watches DecodexList evolutions, stores them and let its client access them.",
			"githubURL" => "https://github.com/pierre42100/decodexwatcherapi/",
			"clientURL" => "https://decodexwatcher.communiquons.org/",
			"apiSchema" => "https://swagger.decodexwatcher.communiquons.org/"
		);
	}

}