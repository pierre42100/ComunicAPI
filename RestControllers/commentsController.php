<?php
/**
 * Comments controller
 * 
 * @author Pierre HUBERT
 */

class commentsController {

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
	 * @return string The comment content, if it passed security checks
	 */
	private function get_comment_content(string $name) : string {

		//Get comment content
		if(!isset($_POST[$name]))
			Rest_fatal_error(400, "Please specify the new content of the comment!");
		$comment_content = (string) $_POST[$name];

		//Perform security check
		if(!check_string_before_insert($comment_content))
			Rest_fatal_error(400, "Please check new comment content !");
		
		//Make the comment secure before insertion
		$comment_content = removeHTMLnodes($comment_content);

		//Return comment conent
		return $comment_content;
	}
}