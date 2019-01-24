<?php
/**
 * Call information object
 * 
 * @author Pierre HUBERT
 */

class CallInformation extends BaseUniqueObject {

	//Private fields
	private $conversation_id;
	private $last_active;
	private $members;

	public function __construct(){
		parent::__construct();
		$this->members = array();
	}

	//Conversations ID
	public function set_conversation_id(int $conversation_id){
		$this->conversation_id = $conversation_id;
	}

	public function has_conversation_id() : bool {
		return $this->conversation_id > -1;
	}

	public function get_conversation_id() : int {
		return $this->conversation_id;
	}


	//Last activity
	public function set_last_active(int $last_active){
		$this->last_active = $last_active;
	}

	public function has_last_active() : bool {
		return $this->last_active > -1;
	}

	public function get_last_active() : int {
		return $this->last_active;
	}

	//Call members list
	public function add_member(CallMemberInformation $member) {
		$this->members[] = $member;
	}

	public function get_members() : array {
		return $this->members;
	}
}