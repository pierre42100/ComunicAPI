<?php
/**
 * Restserver controller
 * 
 * Posts controller
 * 
 * @author Pierre HUBERT
 */

class postsController {

	/**
	 * Get user posts
	 * 
	 * @url POST /posts/get_user
	 */
	public function getUserPosts(){

		//Get user ID
		$userID = getPostUserID("userID");

		//Check if user is allowed to access informations or not
		if(!CS::get()->components->user->userAllowed(userID, $userID))
			Rest_fatal_error(401, "You are not allowed to access this user posts !");

		//Check if there is a startpoint for the posts
		if(isset($_POST['startFrom'])){
			$startFrom = toInt($_POST['startFrom']);
		}
		else
			$startFrom = 0; //No start point

		//Get the post of the user
		$posts = CS::get()->components->posts->getUserPosts(userID, $userID, $startFrom);

		return $posts;
	}

}