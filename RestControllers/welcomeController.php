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
			"serviceDescription" => "This is the Comunic API Server.",
			"clientURL" => "https://communiquons.org/",
		);
	}

}