<?php
/**
 * Search result model
 * 
 * @author Pierre HUBERT
 */

class SearchResult {

	//Kind of result
	const KIND_GROUP = 1;
	const KIND_USER = 2;

	//Private fields
	private $kind;
	private $kind_id;

	//Set and get the kind of object
	public function set_kind(int $kind){
		$this->kind = $kind;
	}

	public function has_kind() : bool {
		return $this->kind > 0;
	}

	public function get_kind() : int {
		return $this->kind;
	}

	//Set and get kind id
	public function set_kind_id(int $kind_id){
		$this->kind_id = $kind_id;
	}

	public function has_kind_id() : bool {
		return $this->kind_id > 0;
	}

	public function get_kind_id() : int {
		return $this->kind_id;
	}
}