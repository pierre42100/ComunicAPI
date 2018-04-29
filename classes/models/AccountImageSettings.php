<?php
/**
 * Account Image settings
 * 
 * @author Pierre HUBERT
 */

class AccountImageSettings {

	/**
	 * Visibility : open
	 * 
	 * Everyone
	 */
	const VISIBILITY_OPEN = 3;

	/**
	 * Visibility : public
	 * 
	 * Any signed in personn
	 */
	const VISIBILITY_PUBLIC = 2;

	/**
	 * Visibility : private
	 * 
	 * Only for the personns and its friends
	 */
	const VISIBILITY_FRIENDS = 1;

	//Private fields
	private $visibility = self::VISIBILITY_OPEN;
	private $path;

	//Set and get visibility level
	public function set_visibility_level(int $visibility_level){
		$this->visibility_level = $visibility_level;
	}

	public function get_visibility_level() : int {
		return $this->visibility_level;
	}

	//Set and get account image path
	public function set_image_path(string $image_path){
		$this->image_path = $image_path == "" ? null : $image_path;
	}

	public function has_image_path() : bool {
		return $this->image_path != null;
	}

	public function get_image_path() : string {
		return $this->image_path != null ? $this->image_path : "null";
	}
}