<?php
/**
 * Information about a conversation
 * 
 * @author Pierre HUBERT
 */

class ConversationInfo extends BaseUniqueObject {

	//Private fields
	private $id_owner;
	private $last_active;
	private $name;
	private $following;
	private $saw_last_message;
	private $members;

	/**
	 * Public constructor
	 */
	public function __construct(){
		parent::__construct();
		
		$this->members = array();
	}

	//Set and get owner ID
	public function set_id_owner(int $id_owner){
		$this->id_owner = $id_owner;
	}

	public function get_id_owner() : int {
		return $this->id_owner;
	}

	//Set and get last activity time
	public function set_last_active(int $last_active){
		$this->last_active = $last_active;
	}

	public function get_last_active() : int {
		return $this->last_active;
	}

	//Set and get conversation name
	public function set_name(string $name){
		$this->name = $name == "" ? null : $name;
	}

	public function has_name() : bool {
		return $this->name != null;
	}

	public function get_name() : string {
		return $this->name != null ? $this->name : "null";
	}

	//Set and get following state of the conversation
	public function set_following(bool $following){
		$this->following = $following;
	}

	public function is_following() : bool {
		return $this->following;
	}

	//Set and get saw last message status
	public function set_saw_last_message(bool $saw_last_message){
		$this->saw_last_message = $saw_last_message;
	}

	public function is_saw_last_message() : bool {
		return $this->saw_last_message;
	}

	//Set and get the members of the conversation
	public function set_members(array $members){
		$this->members = $members;
	}

	public function add_member(int $member){
		$this->members[] = $member;
	}

	public function get_members() : array {
		return $this->members;
	}
}