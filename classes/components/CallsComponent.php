<?php
/**
 * Calls components
 * 
 * @author Pierre HUBERT
 */

class CallsComponents {

	/**
	 * Get and return calls configuration
	 * 
	 * @return CallsConfig Calls configuration / invalid object
	 * if none found (empty config)
	 */
	public function getConfig() : CallsConfig {

		$callConfig = cs()->config->get("calls");

		//If no call config was found
		if(!$callConfig || !is_array($callConfig) || !$callConfig["enabled"])
			return new CallsConfig();

		$config = new CallsConfig();
		$config->set_enabled($callConfig["enabled"]);
		$config->set_signal_server_name($callConfig["signal_server_name"]);
		$config->set_signal_server_port($callConfig["signal_server_port"]);
		$config->set_stun_server($callConfig["stun_server"]);
		$config->set_turn_server($callConfig["turn_server"]);
		$config->set_turn_username($callConfig["turn_username"]);
		$config->set_turn_password($callConfig["turn_password"]);

		return $config;
	}

}

//Register class
Components::register("calls", new CallsComponents());