<?php
/**
 * Survey choice model
 * 
 * @author Pierre HUBERT
 */

class SurveyChoice extends BaseUniqueObject {

	//Private fields
	private $name;
	private $responses;

	//Set and get name
	public function set_name(string $name){
		$this->name = $name == "" ? null : $name;
	}

	public function has_name() : bool {
		return $this->name != null;
	}

	public function get_name() : string {
		return $this->name != null ? $this->name : "null";
	}

	//Set and get the number of responses
	public function set_responses(int $responses){
		$this->responses = $responses;
	}

	public function has_responses() : bool {
		return $this->responses > 0;
	}

	public function get_responses() : int {
		return $this->responses;
	}

}