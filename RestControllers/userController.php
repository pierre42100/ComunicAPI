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
	 * @url POST /user/getInfo
	 * @url POST /user/getInfoMultiple
	 * @return array The result
	 */
	public function getUserInfo() : array{

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
			$usersInfo = CS::get()->components->user->getMultipleUserInfos($usersID);
		else {
			//Divide request in multiples ones
			$usersInfo = array();
			foreach(array_chunk($usersID, 10) as $process_users_ID){
				
				//Get informations about the IDS
				foreach(CS::get()->components->user->getMultipleUserInfos($process_users_ID) as $key=>$val){
					$usersInfo[$key] = $val;
				}
			}
		}
		
		//Check if response is empty
		if(count($usersInfo) == 0)
			throw new RestException(401, "Couldn't get user data !");
		
		//Parse User objects into API-readable objects
		foreach($usersInfo as $num=>$info){
			$usersInfo[$num] = $this->userToAPI($info);
		}

		//Return result
		return $usersInfo;
	}

	/**
	 * Get advanced user informations
	 *
	 * @url POST /user/getAdvancedUserInfos
	 * @url POST /user/getAdvancedUserInfo
	 */
	public function getAdvancedInfo(){

		//Get the ID of the target user
		$userID = getPostUserID("userID");

		//Check if the user is allowed to get advanced user infromations
		if(!CS::get()->components->user->userAllowed(userID, $userID))
			Rest_fatal_error(401, "You are not allowed to access these information !");
		
		//Get user informations
		$userInfos = CS::get()->components->user->getUserAdvancedInfo($userID, true);

		//Check if we got a response
		if(!$userInfos->isValid())
			Rest_fatal_error(500, "Couldn't get informations about the user !");
		
		//Parse user information for the API
		$data = $this->advancedUserToAPI($userInfos);

		//Get the number of friends (if allowed)
		if($userInfos->is_friendListPublic()){
			$data['number_friends'] = CS::get()->components->friends->count_all($userID);
		}
		else
			//User friends won't be displayed
			$data["number_friends"] = 0;

		//User can not post text on this page by default
		$data["can_post_texts"] = FALSE;
		
		//Get some informations only is user is signed in
		if(user_signed_in()){
			$data["user_like_page"] = CS::get()->components->likes->is_liking(userID, $userID, Likes::LIKE_USER);

			//Check if the user can post texts on this page
			$data["can_post_texts"] = 
				//If it is his page, yes by default
				userID == $userID ? TRUE : 
				//Else check friendship status
				CS::get()->components->user->canCreatePosts(userID, $userID);
		}
		
		//Return user informations
		return $data;

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

	/**
	 * Turn a User object into an API array
	 * 
	 * @param User $user Information about the user
	 * @return array Information about the user compatible with the API
	 */
	public static function userToAPI(User $user) : array {

		$data = array();

		$data['userID'] = $user->get_id();
		$data['firstName'] = $user->get_firstName();
		$data['lastName'] = $user->get_lastName();
		$data['publicPage'] = $user->is_publicPage() ? "true" : "false";
		$data['openPage'] = $user->is_openPage() ? "true" : "false";
		$data['virtualDirectory'] = $user->has_virtualDirectory() ? $user->get_virtualDirectory() : "";
		$data['accountImage'] = $user->get_accountImageURL();

		return $data;
	}

	/**
	 * Turn an AdvancedUser object into an API array
	 * 
	 * @param AdvancedUser $user Information about the user
	 * @return array Data compatible with the API
	 */
	public static function advancedUserToAPI(AdvancedUser $user) : array {

		$data = self::userToAPI($user);

		$data['friend_list_public'] = $user->is_friendListPublic();
		$data['personnalWebsite'] = $user->has_personnalWebsite() ? $user->get_personnalWebsite() : "";
		$data['publicNote'] = $user->has_publicNote() ? $user->get_publicNote() : "";
		$data['noCommentOnHisPage'] = $user->is_disallowComments();
		$data['allowPostFromFriendOnHisPage'] = $user->is_allowPostFromFriends();
		$data['account_creation_time'] = $user->get_creation_time();
		$data['backgroundImage'] = $user->get_backgroundImage();
		$data['pageLikes'] = $user->get_pageLikes();

		return $data;
	}
}