<?php
/**
 * Survey model
 * 
 * @author Pierre HUBERT
 */

class Survey extends BaseUniqueObjectFromUser {

	//Private fields
	private $postID = 0;
	private $question;
	private $user_choice = 0;
	private $choices;

	/**
	 * Public constructor
	 */
	public function __construct(){
		parent::__construct();

		//Initialize choices list
		$this->choices = array();
	}

	//Set and get post ID
	public function set_postID(int $postID){
		$this->postID = $postID;
	}

	public function has_postID() : bool {
		return $this->postID > 0;
	}

	public function get_postID() : int {
		return $this->postID;
	}

	//Set and get question
	public function set_question(string $question){
		$this->question = $question == "" ? null : $question;
	}

	public function has_question() : bool {
		return $this->question != null;
	}

	public function get_question() : string {
		return $this->question != null ? $this->question : "null";
	}

	//Set and get user choice
	public function set_user_choice(int $user_choice){
		$this->user_choice = $user_choice;
	}

	public function has_user_choice() : bool {
		return $this->user_choice > 0;
	}

	public function get_user_choice() : int {
		return $this->user_choice;
	}

	//Set and get choices
	public function set_choices(array $choices){
		$this->choices = $choices;
	}

	public function add_choice(array $choice){
		$this->choices[] = $choice;
	}

	public function has_choices() : bool {
		return count($this->choices) > 0;
	}

	public function get_choices() : array {
		return $this->choices;
	}
}