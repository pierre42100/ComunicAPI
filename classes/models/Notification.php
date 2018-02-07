<?php
/**
 * Notification model
 * 
 * @author Pierre HUBERT
 */

class Notification {

	/**
	 * Elements type
	 */
	const CONVERSATION = "conversation";
	const POST_TEXT = "post";
	const POST_IMAGE = "post_img";
	const POST_YOUTUBE = "post_youtube";
	const POST_MOVIE = "post_movie";
	const POST_WEBLINK = "post_weblink";
	const POST_PDF = "post_pdf";
	const POST_TIMER = "post_timer";
	const POST_SURVEY = "post_survey";
	const COMMENT = "comment";

	/**
	 * Event type
	 */
	const ELEM_CREATED = "elem_created";
	const ELEM_UPDATED = "elem_updated";

	//Private fields
	private $id;
	private $time;
	private $seen;
	private $from_user_id;
	private $dest_user_id;
	private $on_elem_id;
	private $on_elem_type;
	private $type;
	private $from_container_id;
	private $from_container_type;

	/**
	 * Set notification id
	 * 
	 * @param int $notificatID The ID of the notification
	 */
	public function set_id(int $id){
		$this->id = $id;
	}

	/**
	 * Get notification iD
	 * 
	 * @return int The ID of the notification
	 */
	public function get_id() : int {
		return $this->id;
	}

	/**
	 * Set notification creation time
	 * 
	 * @param int $time The creation time
	 */
	public function set_time(int $time){
		$this->time = $time;
	}

	/**
	 * Get notification time
	 * 
	 * @return int The time of the notification
	 */
	public function get_time() : int {
		return $this->time;
	}

	/**
	 * Set the seen state of the notification
	 * 
	 * @param bool $seen TRUE for seen
	 */
	public function set_seen(bool $seen){
		$this->seen = $seen;
	}

	/**
	 * Get the seen state of the notification
	 * 
	 * @return bool TRUE if the notification has been seen
	 * FALSE else
	 */
	public function is_seen() : bool {
		return $this->seen;
	}

	/**
	 * Set notification source user id
	 * 
	 * @param int $from_user_id The ID of the user
	 */
	public function set_from_user_id(int $from_user_id){
		$this->from_user_id = $from_user_id;
	}

	/**
	 * Get notification source user id
	 * 
	 * @return int The id of the user who created the notification
	 */
	public function get_from_user_id() : int {
		return $this->from_user_id;
	}

	/**
	 * Set notification destination user id
	 * 
	 * @param int $dest_user_id The ID of the destination
	 */
	public function set_dest_user_id(int $dest_user_id){
		$this->dest_user_id = $dest_user_id;
	}

	/**
	 * Get notification destination user id
	 * 
	 * @return int The dest_user_id of the destination
	 */
	public function get_dest_user_id() : int {
		return $this->dest_user_id;
	}

	/**
	 * Set notification target element id
	 * 
	 * @param int $elem_id The ID of the target element ID
	 */
	public function set_on_elem_id(int $on_elem_id){
		$this->on_elem_id = $on_elem_id;
	}

	/**
	 * Get notification target element id
	 * 
	 * @return int The on_elem_id of the notification
	 */
	public function get_on_elem_id() : int {
		return $this->on_elem_id;
	}

	/**
	 * Set notification target element type
	 * 
	 * @param string $elem_type The type of the target element type
	 */
	public function set_on_elem_type(string $on_elem_type){
		$this->on_elem_type = $on_elem_type;
	}

	/**
	 * Get notification target element type
	 * 
	 * @return string The on_elem_type of the notification
	 */
	public function get_on_elem_type() : string {
		return $this->on_elem_type;
	}

	/**
	 * Set notification event type
	 * 
	 * @param string $type The type of the notification
	 */
	public function set_type(string $type){
		$this->type = $type;
	}

	/**
	 * Get notification event type
	 * 
	 * @return string The type of the notification
	 */
	public function get_type() : string {
		return $this->type;
	}

	/**
	 * Set notification target container element type (if any)
	 * 
	 * @param int $from_container_id The type of the target container element id
	 */
	public function set_from_container_id(int $from_container_id){
		$this->from_container_id = $from_container_id;
	}

	/**
	 * Get notification target container element id
	 * 
	 * @return int The from_container_id of the notification
	 */
	public function get_from_container_id() : int {
		return $this->from_container_id;
	}
}