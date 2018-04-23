<?php
/**
 * Conversation message object
 * 
 * @author Pierre HUBERT
 */

class ConversationMessage extends BaseUniqueObjectFromUser {

	//Private fields
	private $image_path;
	private $message;

	//Set and get image path
	public function set_image_path(string $image_path){
		$this->image_path = $image_path == "" ? null : $image_path;
	}

	public function has_image_path() : bool {
		return $this->image_path != null;
	}

	public function get_image_path() : string {
		return $this->image_path != null ? $this->image_path : "null";
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