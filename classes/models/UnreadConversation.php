<?php
/**
 * Unread conversation model
 * 
 * @author Pierre HUBERT
 */

class UnreadConversation extends BaseUniqueObjectFromUser {

	//Private fields
	private $conv_name;
	private $last_active = -1;
	private $message;

	//Set and get conversation name
	public function set_conv_name(string $conv_name){
		$this->conv_name = $conv_name == "" ? null : $conv_name;
	}

	public function has_conv_name() : bool {
		return $this->conv_name != null;
	}

	public function get_conv_name() : string {
		return $this->conv_name != null ? $this->conv_name : "null";
	}

	//Set and get last conversation activity
	public function set_last_active(int $last_active){
		$this->last_active = $last_active;
	}

	public function has_last_active() : bool {
		return $this->last_active > 0;
	}

	public function get_last_active() : int {
		return $this->last_active;
	}

	//Set and get message
	public function set_message(string $message){
		$this->message = $message == "" ? null : $message;
	}

	public function has_message() : bool {
		return $this->message != null;
	}

	public function get_message() : string {
		return $this->message != null ? $this->message : "null";
	}

}