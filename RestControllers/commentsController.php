<?php
/**
 * Comments controller
 * 
 * @author Pierre HUBERT
 */

class commentsController {

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
}