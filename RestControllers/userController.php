<?php
/**
 * Main user controller file
 *
 * @author Pierre HUBERT
 */

//Enable access to exceptions handler
use \Jacwright\RestServer\RestException;

class userController
{

	/**
	 * Get informations about a user
	 *
	 * @url POST /user/getInfos
	 * @url POST /user/getInfosMultiple
	 * @return array The result
	 */
	public function getUserInfos() : array{

		//Determine userID
		if(isset($_POST['userID'])){
			$usersID = array(toInt($_POST['userID']));
		}
		elseif(isset($_POST['usersID'])){
			//Generate users ID list
			$usersID = numbers_list_to_array($_POST['usersID']);
			
			//Check for errors
			if(count($usersID) == 0)
				Rest_fatal_error(400, "No user ID were specified!");
		}
		else
			//No ID specified
			Rest_fatal_error(400, "Please specify at least one user ID !");

		//Check if it is a wide request or not
		if(count($usersID) <= 10)
			//Try to get user infos
			$userInfos = CS::get()->components->user->getMultipleUserInfos($usersID);
		else {
			//Divide request in multiples ones
			$userInfos = array();
			foreach(array_chunk($usersID, 10) as $process_users_ID){
				
				//Get informations about the IDS
				foreach(CS::get()->components->user->getMultipleUserInfos($process_users_ID) as $key=>$val){
					$userInfos[$key] = $val;
				}
			}
		}
		
		//Check if response is empty
		if(count($userInfos) == 0)
			throw new RestException(401, "Couldn't get user data !");
		
		//Return result
		return $userInfos;
	}

	/**
	 * Get advanced user informations
	 *
	 * @url POST /user/getAdvancedUserInfos
	 */
	public function getAdvancedInfos(){

		//Get the ID of the target user
		$userID = getPostUserID("userID");

		//Check if the user is allowed to get advanced user infromations
		if(!CS::get()->components->user->userAllowed(userID, $userID))
			Rest_fatal_error(401, "You are not allowed to access these information !");
		
		//Get user informations
		$userInfos = CS::get()->components->user->getUserInfos($userID, true);

		//Check if we got a response
		if(count($userInfos) == 0)
			Rest_fatal_error(500, "Couldn't get informations about the user !");
		

		//Get the number of friends (if allowed)
		if($userInfos['friend_list_public'] === true){
			$userInfos['number_friends'] = CS::get()->components->friends->count_all($userID);
		}
		else
			//User friends won't be displayed
			$userInfos["number_friends"] = 0;

		//User can not post text on this page by default
		$userInfos["can_post_texts"] = FALSE;
		
		//Get some informations only is user is signed in
		if(user_signed_in()){
			$userInfos["user_like_page"] = CS::get()->components->likes->is_liking(userID, $userID, Likes::LIKE_USER);

			//Check if the user can post texts on this page
			$userInfos["can_post_texts"] = 
				//If it is his page, yes by default
				userID == $userID ? TRUE : 
				//Else check friendship status
				CS::get()->components->user->canCreatePosts(userID, $userID);
		}
		
		//Return user informations
		return $userInfos;

	}

	/**
	 * Get current user infos using tokens
	 *
	 * @url POST /user/getCurrentUserID
	 */
	public function getCurrentUserID(){
		user_login_required();

		//Update last user activity
		CS::get()->components->user->updateLastActivity(userID);

		//Return userID
		return array("userID" => userID);
	}

	/**
	 * Find user ID by a specified folder name
	 *
	 * @url POST /user/findbyfolder
	 */
	public function findUserByFolder(){

		//Check for domain name
		if(!isset($_POST['subfolder']))
			Rest_fatal_error(400, "No subfolder specified!");

		$input = safe_for_sql($_POST['subfolder']);

		if(!check_string_before_insert($input))
			Rest_fatal_error(401, "The request was cancelled because the query is unsafe !");

		//Search user ID in the database
		$id = CS::get()->components->user->findByFolder($input);

		//Check for error
		if($id === 0)
			Rest_fatal_error(404, "No user was found with the specifed subfolder!");
		
		//Return result
		return array("userID" => $id);

	}
}