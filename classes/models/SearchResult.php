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

	/**
	 * Constructor of the object
	 * 
	 * @param int $kind The kind of result (group, user...)
	 * @param int $kind_id The ID of the result
	 */
	public function SearchResult(int $kind, int $kind_id){
		$this->set_kind($kind);
		$this->set_kind_id($kind_id);
	}

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