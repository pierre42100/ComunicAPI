<?php

/**
 * Calls controller
 * 
 * @author Pierre HUBERT
 */

class CallsController {

	/**
	 * Get call configuration
	 * 
	 * @url POST /calls/config
	 */
	public function getConfig(){
		user_login_required();
		return self::CallsConfigToAPI(components()->calls->getConfig());
	}


	/**
	 * Turn a CallsConfig object into an API entry
	 * 
	 * @param $config The config to convert
	 * @return array Generated API entry
	 */
	private static function CallsConfigToAPI(CallsConfig $config) : array {

		$data = array();

		$data["enabled"] = $config->get_enabled();

		//Give full configuration calls are enabled
		if($config->get_enabled()){
			$data["signal_server_name"] = $config->get_signal_server_name();
			$data["signal_server_port"] = $config->get_signal_server_port();
			$data["stun_server"] = $config->get_stun_server();
			$data["turn_server"] = $config->get_turn_server();
			$data["turn_username"] = $config->get_turn_username();
			$data["turn_password"] = $config->get_turn_password();
		}

		return $data;
	}
}