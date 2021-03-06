<?php
/**
 * API likes controller
 * 
 * @author Pierre HUBERT
 */

class LikesController {

	/**
	 * Update a specific component like
	 * 
	 * @url POST /likes/update
	 */
	public function update_like(){

		user_login_required(); //Need login

		//Get component kind
		if(!isset($_POST["type"]))
			Rest_fatal_error(401, "Please specify like type !");
		
		$type = $_POST["type"];

		//Check new status for the like
		if(!isset($_POST['like']))
			Rest_fatal_error(400, "Please specify the new like status with the request !");
		$like = $_POST['like'] == "true";

		//Find the right component type for checks
		switch($type){

			//In case of user
			case "user":

				//Extract informations
				$id = getPostUserId("id");
				$componentType = Likes::LIKE_USER;

				//Check if user can access page
				if(!CS::get()->components->user->userAllowed(userID, $id))
					Rest_fatal_error(401, "You can not access this user information !");

				break;
			

			//In case of post
			case "post":

				//Extract informations
				$id = getPostPostIDWithAccess("id");
				$componentType = Likes::LIKE_POST;

				//Delete any notification targeting this user about the post
				delete_user_notifications_over_post(userID, $id);

				break;
			

				
			//In case of comments
			case "comment":
				
				//Extract informations
				$id = getPostCommentIDWithAccess("id");
				$componentType = Likes::LIKE_COMMENT;

				break;


			//In case of group
			case "group":
				$id = getPostGroupIdWithAccess("id", GroupInfo::VIEW_ACCESS);
				$componentType = Likes::LIKE_GROUP;
				break;
				

			//Default case : error
			default:
				Rest_fatal_error(404, "Specifed component type currently not supported !");
		}

		//Update like status
		CS::get()->components->likes->update(userID, $like, $id, $componentType);

		//Success
		return array("success" => "Like status updated.");
	}


	/**
	 * Turn a UserLike object into valid API entry
	 * 
	 * @param UserLike $object The object to convert
	 * @return array Valid API database entry
	 */
	public static function UserLikeToAPI(UserLike $object) : array {

		$data = array();

		$data["id"] = $object->get_id();
		$data["userID"] = $object->get_userID();
		$data["time_sent"] = $object->get_time_sent();
		$data["elem_type"] = $object->get_elem_type();
		$data["elem_id"] = $object->get_elem_id();

		return $data;
	}
}