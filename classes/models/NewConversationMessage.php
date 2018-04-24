<?php

/**
 * New conversation message object
 * 
 * @author Pierre HUBERT
 */

class NewConversationMessage extends ConversationMessage {

	//Private fields
	private $conversationID;

	//Get and set conversation ID
	public function set_conversationID(int $conversationID){
		$this->conversationID = $conversationID;
	}

	public function get_conversationID() : int {
		return $this->conversationID;
	}

}