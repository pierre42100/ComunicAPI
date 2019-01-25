<?php
/**
 * Calls components
 * 
 * @author Pierre HUBERT
 */

class CallsComponents {

	/**
	 * Calls tables names
	 */
	private const CALLS_LIST_TABLE = "comunic_calls";
	private const CALLS_MEMBERS_TABLE = "comunic_calls_members";

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

	/**
	 * Get the call for a conversation
	 * 
	 * @param $conversation_id Target conversation ID
	 * @param $load_members Specify whether members information should
	 * be loaded too or not
	 * @return CallInformation Matching call information object / invalid object
	 * in case of failure
	 */
	public function getForConversation(int $conversation_id, bool $load_members) : CallInformation {
		
		$entry = db()->select(
			self::CALLS_LIST_TABLE, 
			"WHERE conversation_id = ?",
			array($conversation_id)
		);

		if(count($entry) == 0)
			return new CallInformation();
		
		$info = self::DBToCallInformation($entry[0]);

		//Load call members if required
		if($load_members && !$this->getMembers($info))
			return new CallInformation();


		return $info;
	}

	/**
	 * Get information about a call
	 * 
	 * @param $call_id Target call ID
	 * @param $load_members Specify whether members information should
	 * be loaded too or not
	 * @return CallInformation Matching call information object / invalid object
	 * in case of failure
	 */
	public function get(int $call_id, bool $load_members) : CallInformation {
		
		$entry = db()->select(
			self::CALLS_LIST_TABLE, 
			"WHERE id = ?",
			array($call_id)
		);

		if(count($entry) == 0)
			return new CallInformation();
		
		$info = self::DBToCallInformation($entry[0]);

		//Load call members if required
		if($load_members && !$this->getMembers($info))
			return new CallInformation();


		return $info;
	}

	/**
	 * Get the next call for a user
	 * 
	 * @param $userID Target user ID
	 * @param $load_members Specify whether information about members 
	 * should be loaded or not
	 * @return CallInformation Information about the call / invalid object
	 * if none found
	 */
	public function getNextPendingForUser(int $userID, bool $load_members) : CallInformation {

		//Get the ID of a call the user has not responded yet
		$entries = db()->select(
			self::CALLS_MEMBERS_TABLE,
			"WHERE user_id = ? AND user_accepted = ?",
			array($userID, CallMemberInformation::USER_UNKNOWN),
			array("call_id")
		);

		//Check if the user has no pending call
		if(count($entries) == 0)
			return new CallInformation();
		
		return $this->get($entries[0]["call_id"], $load_members);

	}

	/**
	 * Create a call for a conversation
	 * 
	 * @param $conversationID The ID of the target conversation
	 * @return bool TRUE for a success / FALSE else
	 */
	public function createForConversation(int $conversationID) : bool {

		//Generate call information
		$info = new CallInformation();
		$info->set_conversation_id($conversationID);
		$info->set_last_active(time());

		//We need to get the list of members of the conversation to create members list
		$conversation_members = components()->conversations->getConversationMembers($conversationID);

		//Check for errors
		if(count($conversation_members) == 0)
			return false;

		
		//Insert the call in the database to get its ID
		if(!db()->addLine(
			self::CALLS_LIST_TABLE,
			self::CallInformationToDB($info)
		))
			return false;
		$info->set_id(db()->getLastInsertedID());

		foreach($conversation_members as $memberID){
			$member = new CallMemberInformation();
			$member->set_call_id($info->get_id());
			$member->set_userID($memberID);
			$member->set_user_call_id(random_str(190));
			$member->set_accepted(CallMemberInformation::USER_UNKNOWN);

			//Try to add the member to the list
			if(!$this->addMember($member))
				return false;
		}

		//Success
		return true;
	}

	/**
	 * Add a new member to a call
	 * 
	 * @param $member Information about the member to add
	 * @return bool TRUE for a success / FALSE else
	 */
	private function addMember(CallMemberInformation $member) : bool {
		return db()->addLine(
			self::CALLS_MEMBERS_TABLE,
			self::CallMemberInformationToDB($member)
		);
	}

	/**
	 * Load information about the members related to the call
	 * 
	 * @param $info Information about the call to process
	 * @return bool TRUE in case of success / FALSE else
	 */
	private function getMembers(CallInformation $info) : bool {

		$entries = db()->select(
			self::CALLS_MEMBERS_TABLE,
			"WHERE call_id = ?",
			array($info->get_id())
		);
		
		foreach($entries as $entry)
			$info->add_member(self::DBToCallMemberInformation($entry));
		
		return count($entries) > 0;
	}

	/**
	 * Count and return the number of pending calls for a user
	 * 
	 * @param $userID The ID of the target user
	 * @return int The number of pending calls for the user
	 */
	public function countPendingResponsesForUser(int $userID) : int {

		return db()->count(
			self::CALLS_MEMBERS_TABLE,
			"WHERE user_id = ? AND user_accepted = ?",
			array($userID, CallMemberInformation::USER_UNKNOWN)
		);

	}

	/**
	 * Check out whether a user belongs to a call or not
	 * 
	 * @param $callID The ID of the target call
	 * @param $userID The ID of the target user
	 * @return bool TRUE if the user belongs to the call / FALSE else
	 */
	public function doesUserBelongToCall(int $callID, int $userID) : bool {
		return db()->count(
			self::CALLS_MEMBERS_TABLE,
			"WHERE call_id = ? AND user_id = ?",
			array($callID, $userID)
		) > 0;
	}

	/**
	 * Set the response of a member to a call
	 * 
	 * @param $callID The ID of the target call
	 * @param $userID The ID of the target member
	 * @param $accept TRUE to accept the call / FALSE else
	 * @return bool TRUE for a success / FALSE else
	 */
	public function setMemberResponse(int $callID, int $userID, bool $accept) : bool {
		db()->updateDB(
			self::CALLS_MEMBERS_TABLE,
			"call_id = ? AND user_id = ?",
			array(
				"user_accepted" => 
					$accept ? CallMemberInformation::USER_ACCEPTED : CallMemberInformation::USER_REJECTED
			),
			array(
				$callID,
				$userID
			)
		);

		return true;
	}

	/**
	 * Turn a database entry into a CallInformation object
	 * 
	 * @param $entry The entry to convert
	 * @return CallInformation Generated object
	 */
	private static function DBToCallInformation(array $entry) : CallInformation {
		$info = new CallInformation();
		$info->set_id($entry["id"]);
		$info->set_conversation_id($entry["conversation_id"]);
		$info->set_last_active($entry["last_active"]);
		return $info;
	}

	/**
	 * Turn a CallInformation object into a database entry
	 * 
	 * @param $call Call information object to convert
	 * @return array Generated array
	 */
	private static function CallInformationToDB(CallInformation $call) : array {
		$data = array();
		$data["conversation_id"] = $call->get_conversation_id();
		$data["last_active"] = $call->get_last_active();
		return $data;
	}

	/**
	 * Turn a database entry into a CallMemberInformation object
	 * 
	 * @param $entry The entry to convert
	 * @return CallMemberInformation Generated object
	 */
	private static function DBToCallMemberInformation(array $entry) : CallMemberInformation {
		$member = new CallMemberInformation();
		$member->set_id($entry["id"]);
		$member->set_call_id($entry["call_id"]);
		$member->set_userID($entry["user_id"]);
		$member->set_user_call_id($entry["user_call_id"]);
		$member->set_accepted($entry["user_accepted"]);
		return $member;
	}

	/**
	 * Turn a CallMemberInformation object into a database entry
	 * 
	 * @param $member The member to convert
	 * @return array Generated database entry
	 */
	private static function CallMemberInformationToDB(CallMemberInformation $member) : array {
		$data = array();
		$data["call_id"] = $member->get_call_id();
		$data["user_id"] = $member->get_userID();
		$data["user_call_id"] = $member->get_user_call_id();
		$data["user_accepted"] = $member->get_accepted();
		return $data;
	}
}

//Register class
Components::register("calls", new CallsComponents());