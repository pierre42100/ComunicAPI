<?php
/**
 * API Actions limits count
 * 
 * @author Pierre HUBERT
 */

class APILimits {

	/**
	 * Table name
	 */
	const TABLE_NAME = DBprefix."api_limit_count";

	/**
	 * Entries live time
	 */
	const KEEP_DATA_FOR = 3600; // 1 hour

	/**
	 * Actions list
	 */
	const ACTION_LOGIN_FAILED = "failed_login";
	const ACTION_CREATE_ACCOUNT = "create_account";

	/**
	 * Actions configruation
	 */
	const ACTIONS = array(

		//Login failed
		self::ACTION_LOGIN_FAILED => array(
			"limit" => 10
		),

		//Create an account
		self::ACTION_CREATE_ACCOUNT => array(
			"limit" => 10
		),
	);

	/**
	 * Limit the number of time a client can perform a query over the API
	 * 
	 * @param string $action The name of the action to limit
	 * @param bool $trigger Specify whether this call of the method must be
	 * considered as a call of the client or not
	 */
	public function limit_query(string $action, bool $trigger){

		//First, clean old entries
		$this->clean();

		$ip = $_SERVER["REMOTE_ADDR"];

		//If required, increase action by one
		if($trigger)
			$this->trigger($action, $ip);
		
		//Count the number of time the action occurred
		if($this->count($action, $ip) > self::ACTIONS[$action]["limit"])
			Rest_fatal_error(429, "Too many request. Please try again later.");
	}

	/**
	 * Clean old entries
	 */
	public function clean(){
		db()->deleteEntry(
			self::TABLE_NAME,
			"time_start < ?",
			array(time() - self::KEEP_DATA_FOR)
		);
	}

	/**
	 * Increase by one the number of the time a client performed
	 * an action
	 * 
	 * @param string $action The action to trigger
	 * @param string $ip The target IP address
	 * @return bool TRUE for a success else FALSE
	 */
	private function trigger(string $action, string $ip) : bool {

		if(!$this->exists($action, $ip)){
			return db()->addLine(self::TABLE_NAME, array(
				"ip" => $ip,
				"time_start" => time(),
				"action" => $action,
				"count" => 1
			));
		}

		else {
			
			$number = $this->count($action, $ip);
			$number++;

			return db()->updateDB(self::TABLE_NAME,
				"ip = ? AND action = ?",
				array("count" => $number),
				array($ip, $action));
		}

	}

	/**
	 * Check wether an action has been referenced at least once in
	 * the database
	 * 
	 * @param string $action The action to check
	 * @param string $ip The target IP address
	 * @return bool TRUE if the entry has been found at least once / FALSE else
	 */
	private function exists(string $action, string $ip) : bool {
		return db()->count(self::TABLE_NAME, 
			"WHERE ip = ? AND action = ?",
			array($ip, $action)) > 0;
	}

	/**
	 * Count the number of time an IP address has performed an action
	 * 
	 * @param string $action The target action
	 * @param string $ip Target IP address
	 * @return int The number of time the action has been done
	 */
	private function count(string $action, string $ip) : int {
		$data = db()->select(self::TABLE_NAME, 
			"WHERE ip = ? AND action = ?",
			array($ip, $action),
			array("count"));
		
		if(count($data) < 1)
			return 1;
		
		else
			return $data[0]["count"];
	}
}