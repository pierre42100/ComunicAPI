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
	const USER_PAGE = "user_page";
	const GROUP_PAGE = "group_page";
	const CONVERSATION = "conversation";
	const CONVERSATION_MESSAGE = "conversation_message";
	const POST = "post";
	const POST_TEXT = "post_text";
	const POST_IMAGE = "post_img";
	const POST_YOUTUBE = "post_youtube";
	const POST_MOVIE = "post_movie";
	const POST_WEBLINK = "post_weblink";
	const POST_PDF = "post_pdf";
	const POST_TIMER = "post_timer";
	const POST_SURVEY = "post_survey";
	const COMMENT = "comment";
	const FRIENDSHIP_REQUEST = "friend_request";

	/**
	 * Event type
	 */
	const COMMENT_CREATED = "comment_created";
	const SENT_FRIEND_REQUEST = "sent_friend_request";
	const ACCEPTED_FRIEND_REQUEST = "accepted_friend_request";
	const REJECTED_FRIEND_REQUEST = "rejected_friend_request";
	const ELEM_CREATED = "elem_created";
	const ELEM_UPDATED = "elem_updated";

	/**
	 * Event visibility
	 */
	const EVENT_PRIVATE = "event_private";
	const EVENT_PUBLIC = "event_public";


	//Private fields
	private $id;
	private $time_create;
	private $seen;
	private $from_user_id;
	private $dest_user_id;
	private $on_elem_id;
	private $on_elem_type;
	private $type;
	private $event_visibility;
	private $from_container_id;
	private $from_container_type;

	/**
	 * Default constructor of the notification
	 */
	public function __construct(){
		
		//Notification not seen by default
		$this->seen = false;

		//By default, the notification does not have any contener
		$this->from_container_id = 0;
		$this->from_container_type = "";

	}

	/**
	 * Set notification id
	 * 
	 * @param int $notificatID The ID of the notification
	 */
	public function set_id(int $id){
		$this->id = $id;
	}

	/**
	 * Get notification ID
	 * 
	 * @return int The ID of the notification
	 */
	public function get_id() : int {
		return $this->id;
	}

	/**
	 * Check if the notification object has an ID or not
	 * 
	 * @return bool TRUE if the notification as an ID / FALSE else
	 */
	public function has_id() : bool {
		return ($this->id != null) && ($this->id != 0);
	}

	/**
	 * Set notification creation time
	 * 
	 * @param int $time_create The creation time
	 */
	public function set_time_create(int $time_create){
		$this->time_create = $time_create;
	}

	/**
	 * Get notification creation time
	 * 
	 * @return int The creation time of the notification
	 */
	public function get_time_create() : int {
		return $this->time_create;
	}

	/**
	 * Check if the notification has a specified creation time
	 * 
	 * @return bool TRUE if the creation time was specified
	 */
	public function has_time_create() : bool {
		return $this->time_create != null;
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
	 * Check if the seen state of the notification has been set or not
	 * 
	 * @return bool TRUE if the seen state of the notification is set / FALSE else
	 */
	public function has_seen_state() : bool {
		return $this->seen != null;
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
	 * Check if the notification has a given user source
	 * 
	 * @return bool TRUE if the source of the notification has been specified / FALSE else
	 */
	public function has_from_user_id() : bool {
		return $this->from_user_id != null;
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
	 * Check if the notification has a destination user ID
	 * 
	 * @return bool TRUE if the destination user ID was specified
	 */
	public function has_dest_user_id() : bool {
		return $this->dest_user_id != null;
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
	 * Check if the id of element targeted by the notification has been
	 * specified or not
	 * 
	 * @return bool TRUE if the ID of the target element has been specified
	 */
	public function has_on_elem_id() : bool {
		return $this->on_elem_id != null;
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
	 * Check if the type of element targeted by the notification has been
	 * specified or not
	 * 
	 * @return bool TRUE if the ID of the target element has been specified
	 */
	public function has_on_elem_type() : bool {
		return $this->on_elem_type != null;
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
	 * Check if the notification has a given creation time
	 * 
	 * @return bool TRUE if the notification has a type / FALSE else
	 */
	public function has_type() : bool {
		return $this->type != null;
	}

	/**
	 * Set notification event visibility
	 * 
	 * @param string $visibility The visibility of the notification
	 */
	public function set_event_visibility(string $event_visibility){
		$this->event_visibility = $event_visibility;
	}

	/**
	 * Get notification event visibility
	 * 
	 * @return string The visibility of the notification
	 */
	public function get_event_visibility() : string {
		return $this->event_visibility;
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

	/**
	 * Set notification target element type
	 * 
	 * @param string $elem_type The type of the target element type
	 */
	public function set_from_container_type(string $from_container_type){
		$this->from_container_type = $from_container_type;
	}

	/**
	 * Get notification target element type
	 * 
	 * @return string The from_container_type of the notification
	 */
	public function get_from_container_type() : string {
		return $this->from_container_type;
	}
}