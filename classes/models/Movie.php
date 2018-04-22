<?php
/**
 * Movie model
 * 
 * @author Pierre HUBERT
 */

class Movie extends BaseUniqueObjectFromUser {

	//Private fields
	private $uri;
	private $url;
	private $name;
	private $file_type;
	private $size;

	//Set and get uri
	public function set_uri(string $uri){
		$this->uri = $uri == "" ? null : $uri;
	}

	public function has_uri() : bool {
		return $this->uri != null;
	}

	public function get_uri() : string {
		return $this->uri != null ? $this->uri : "null";
	}

	//Set and get url
	public function set_url(string $url){
		$this->url = $url == "" ? null : $url;
	}

	public function has_url() : bool {
		return $this->url != null;
	}

	public function get_url() : string {
		return $this->url != null ? $this->url : "null";
	}

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

	//Set and get file_type
	public function set_file_type(string $file_type){
		$this->file_type = $file_type == "" ? null : $file_type;
	}

	public function has_file_type() : bool {
		return $this->file_type != null;
	}

	public function get_file_type() : string {
		return $this->file_type != null ? $this->file_type : "null";
	}

	//Set and get the size of the file
	public function set_size(int $size){
		$this->size = $size;
	}

	public function has_size() : bool {
		return $this->size > -1;
	}

	public function get_size() : int {
		return $this->size;
	}
}