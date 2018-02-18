<?php
/**
 * Comments controller
 * 
 * @author Pierre HUBERT
 */

class commentsController {

	/**
	 * Create a comment
	 * 
	 * @url POST /comments/create
	 */
	public function create(){

		user_login_required();

		//Get the ID of the associated post
		$postID = getPostPostIDWithAccess("postID");

		//Check if an image was included in the request
		if(check_post_file("image")){

			//Get comment content
			$content = $this->get_comment_content("content", false);

			//Save the image
			$image_path = save_post_image("image", userID, "imgcommentaire", 700, 700);
		}
		
		//Else check the content of the comment before getting it
		else
			$content = $this->get_comment_content("content", true);

		//Try to create the comment
		$commentID = components()->comments->create(
			$postID, 
			userID, 
			$content, 
			isset($image_path) ? $image_path : ""
		);

		//Check for errors
		if($commentID < 1)
			Rest_fatal_error(500, "An error occured while trying to create comment !");


		//Create a notification about the comments created
		$notification = new Notification();
		$notification->set_time_create(time());
		$notification->set_from_user_id(userID);
		$notification->set_on_elem_id($postID);
		$notification->set_on_elem_type(Notification::POST);
		$notification->set_type(Notification::COMMENT_CREATED);
		components()->notifications->push($notification);

		//Delete any other notification targeting this user about this post
		delete_user_notifications_other_post(userID, $postID);

		//Success
		return array(
			"success" => "The comment was created!",
			"commentID" => $commentID
		);
	}

	/**
	 * Get informations about a single comment
	 * 
	 * @url POST /comments/get_single
	 */
	public function get_single_infos(){

		//Get the comment ID
		$commentID = getPostCommentIDWithAccess("commentID");

		//Get informations about the comment
		$infos = components()->comments->get_single($commentID, TRUE);

		//Check for errors
		if(count($infos) == 0)
			Rest_fatal_error(500, "Couldn't fetch informations about the comment !");

		//Return informations about the comment
		return $infos;
	}

	/**
	 * Edit a comment content
	 * 
	 * @url POST /comments/edit
	 */
	public function edit_comment(){

		user_login_required();

		//Get comment ID
		$commentID = $this->getPostCommentIDWithFullAccess("commentID");

		//Get comment content$
		$new_content = $this->get_comment_content("content");

		//Update comment content
		if(!components()->comments->edit($commentID, $new_content))
			Rest_fatal_error(500, "Could not update comment content !");
		
		//Success
		return array("success" => "The comment has been updated !");
	}

	/**
	 * Delete a comment
	 * 
	 * @url POST /comments/delete
	 */
	public function delete_comment(){

		user_login_required();

		//Get comment ID
		$commentID = $this->getPostCommentIDWithFullAccess("commentID");

		//Try to delete the comment
		if(!components()->comments->delete($commentID))
			Rest_fatal_error(500, "Coudln't delete comment!");
		
		//Success
		return array("success" => "The comment has been deleted!");

	}

	/**
	 * Get a comment ID with full access
	 * 
	 * @param string $name The name of the POST field containing
	 * the comment ID
	 * @return int The comment ID
	 */
	private function getPostCommentIDWithFullAccess($name) : int {

		//Get comment ID
		$commentID = getPostCommentIDWithAccess($name);

		//Check the user is the owner of the comment
		if(!components()->comments->is_owner(userID, $commentID))
			Rest_fatal_error(401, "You are not the owner of this comment !");

		//Return comment ID
		return $commentID;
	}

	/**
	 * Get a comment content from $_POST field
	 * 
	 * @param string $name The name of post field containing the commment content
	 * @param bool $need_check TRUE if the comment content has to be checked / FALSE else
	 * @return string The comment content, if it passed security checks
	 */
	private function get_comment_content(string $name, bool $need_check = true) : string {

		//Get comment content
		if(!isset($_POST[$name]))
			Rest_fatal_error(400, "Please specify the new content of the comment!");
		$comment_content = (string) $_POST[$name];

		//Perform security check
		if(!check_string_before_insert($comment_content) && $need_check)
			Rest_fatal_error(400, "Please check new comment content !");
		
		//Make the comment secure before insertion
		$comment_content = removeHTMLnodes($comment_content);

		//Return comment conent
		return $comment_content;
	}
}