<?php

/**
 * Calls controller
 * 
 * @author Pierre HUBERT
 */

class CallsController {

	/**
	 * Single user response to call
	 */
	const USER_RESPONSE_TO_CALL = array(
		CallMemberInformation::USER_ACCEPTED => "accepted",
		CallMemberInformation::USER_REJECTED => "rejected",
		CallMemberInformation::USER_UNKNOWN => "unknown"
	);

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
	 * Create a call for a conversation
	 * 
	 * @url POST /calls/createForConversation
	 */
	public function createForConversation(){

		user_login_required();

		//Get conversation ID
		$conversationID = getPostConversationID("conversationID");

		//Check if the user belongs to the conversation
		if(!components()->conversations->userBelongsTo(userID, $conversationID))
			Rest_fatal_error(401, "Specified user doesn't belongs to the conversation number ".$conversationID." !");

		//First, check if a call alreay exists for this conversation
		$call = components()->calls->getForConversation($conversationID, true);

		//If could not get a call, try to create a new one
		if(!$call->isValid()){

			//Try to create the call
			if(!components()->calls->createForConversation($conversationID))
				Rest_fatal_error(500, "Could not create the call");

			//Get the conversation again
			$call = components()->calls->getForConversation($conversationID, true);

			if(!$call->isValid())
				Rest_fatal_error(500, "Could not get information about created call!");
		}

		//The user automatically accept the call
		components()->calls->setMemberResponse($call->get_id(), userID, true);

		//Returns information about the call
		return self::CallInformationToAPI($call);
			
	}

	/**
	 * Get the next pending call
	 * 
	 * @url POST /calls/nextPending
	 */
	public function GetNextPendingCall(){
		user_login_required();

		//Get the next pending call for the user
		$call = components()->calls->getNextPendingForUser(userID, TRUE);

		if(!$call->isValid())
			return array("notice" => "No pending call.");

		return self::CallInformationToAPI($call);
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

	/**
	 * Turn a CallInformation object into an API entry
	 * 
	 * @param $call The call to convert
	 * @return array Generated API entry
	 */
	private static function CallInformationToAPI(CallInformation $call) : array {
		$data = array(
			"id" => $call->get_id(),
			"conversation_id" => $call->get_conversation_id(),
			"last_active" => $call->get_last_active(),
			"members" => array()
		);

		foreach($call->get_members() as $member)
			$data["members"][] = self::CallMemberInformationToAPI($member);

		return $data;
	}

	/**
	 * Turn a CallMemberInformation object into an API entry
	 * 
	 * @param $member Member information
	 * @return array User information API entry
	 */
	private static function CallMemberInformationToAPI(CallMemberInformation $member) : array {
		return array(
			"id" => $member->get_id(),
			"userID" => $member->get_userID(),
			"call_id" => $member->get_call_id(),
			"user_call_id" => $member->get_user_call_id(),
			"accepted" => self::USER_RESPONSE_TO_CALL[$member->get_accepted()]
		);
	}
}