<?php
/**
 * Main user class
 *
 * @author Pierre HUBERT
 */

class UserComponent {

	/**
	 * @var String $userTable The name of the user table
	 */
	const USER_TABLE = "utilisateurs";

	/**
	 * Pages visiblity levels
	 */
	const USER_PAGE_PRIVATE = 0;
	const USER_PAGE_PUBLIC = 1;
	const USER_PAGE_OPEN = 2;

	/**
	 * Public constructor
	 */
	public function __construct(){
		
	}

	/**
	 * Get advanced information about a user
	 * 
	 * @param int $userID Target user ID
	 * @return AdvancedUser Informations about the user (invalid object in case of failure)
	 */
	public function getUserAdvancedInfo(int $userID) : AdvancedUser {

		//Perform a request over the database
		$data = $this->getDBUserInfo($userID);
		
		if(count($data) == 0)
			return new AdvancedUser(); //Return invalid object
		
		return $this->parseDbToAdvancedUser($data);
	}
	
	/**
	 * Get Single User Infos from database and return its information as an array
	 *
	 * @param int $userID The user ID
	 * @return array Information about the user (empty array in case of failure)
	 */
	private function getDBUserInfo(int $userID) : array {
		//Prepare database request
		$tablesName = self::USER_TABLE;
		$conditions = "WHERE utilisateurs.ID = ?";
		$conditionsValues = array(
			$userID*1,
		);
		
		//Perform request
		$userInfos = CS::get()->db->select($tablesName, $conditions, $conditionsValues);
		
		//Check if result is correct or not
		if(count($userInfos) == 0)
			return array(); //No result
		
		//Return parsed result
		return($userInfos[0]);
	}


	/**
	 * Get Multiple Users Infos
	 *
	 * @param Array $usersID The users ID
	 * @return Array The result of the function (user informations) (empty one if it fails)
	 */
	public function getMultipleUserInfos(array $usersID) : array {
		//Prepare database request
		$tablesName = self::USER_TABLE;
		$conditions = "WHERE (utilisateurs.ID < 0)";
		$conditionsValues = array();

		//Process users
		foreach($usersID as $i=>$process){
			$conditions .= " OR utilisateurs.ID = ?";
			$conditionsValues[] = $process*1;
		}
		
		//Perform request
		$usersInfos = CS::get()->db->select($tablesName, $conditions, $conditionsValues);
		
		//Check if result is correct or not
		if(count($usersInfos) == 0)
			return array(); //No result
		
		//Process result
		foreach($usersInfos as $processUser){
			$result[$processUser['ID']] = $this->generateUserInfosArray($processUser);
		}

		//Return result
		return $result;
	}

	/**
	 * Update last user activity time on the network
	 *
	 * @param int $userID The ID of the user to update
	 * @return bool True for a success
	 */
	public function updateLastActivity(int $userID) : bool{

		//Perform a request on the database
		$tableName = self::USER_TABLE;
		$conditions = "ID = ?";
		$whereValues = array(userID);
		$modifs = array(
			"last_activity" => time()
		);

		if(!CS::get()->db->updateDB($tableName, $conditions, $modifs, $whereValues))
			return false;

		//Success
		return true;
	}

	/**
	 * Check if a user exists or not
	 *
	 * @param int $userID The ID of the user to check
	 * @return bool Depends of the existence of the user
	 */
	public function exists(int $userID) : bool {
		//Perform a request on the database
		$tableName = self::USER_TABLE;
		$condition = "WHERE ID = ?";
		$condValues = array($userID);
		$requiredFields = array("ID");

		//Try to perform request
		$result = CS::get()->db->select($tableName, $condition, $condValues, $requiredFields);

		//Check for errors
		if($result === false)
			return false; //An error occured
		
		//Check and return result
		return count($result) !== 0;
	}

	/**
	 * Find the user specified by a folder name
	 *
	 * @param string $folder The folder of the research
	 * @return int 0 if no user was found or the ID of the user in case of success
	 */
	public function findByFolder(string $folder) : int {

		//Perform a request on the database
		$tableName = self::USER_TABLE;
		$condition = "WHERE sous_repertoire = ?";
		$condValues = array($folder);
		$requiredFields = array("ID");

		//Try to perform the request
		$result = CS::get()->db->select($tableName, $condition, $condValues, $requiredFields);

		//Check for errors
		if($result === false){
			return 0;
		}

		if(count($result) == 0)
			return 0; //There is no result
		
		//Return result
		return $result[0]["ID"];

	}

	/**
	 * Get a user page visibility level
	 *
	 * @param int $id The ID of the user to fetch
	 * @return int The visibility level of the user page
	 * - -1 : In case of failure (will make the protection level elevated)
	 * - 0 : The page is private (for user friends)
	 * - 1 : The page is public (for signed in users)
	 * - 2 : The page is open (for everyone)
	 */
	public function getVisibility(int $userID) : int {

		//Perform a request on the database
		$tableName = self::USER_TABLE;
		$condition = "WHERE ID = ?";
		$condValues = array($userID);

		$requiredFields = array(
			"public",
			"pageouverte"
		);

		//Perform the request
		$result = CS::get()->db->select($tableName, $condition, $condValues, $requiredFields);

		if($result === false){
			return -1;
		}

		//Check for a result
		if(count($result) == 0){
			return -1;
		}

		//Check if the page is public
		if($result[0]["public"] == 0)
			return $this::USER_PAGE_PRIVATE;

		//Check if the page is open or not
		if($result[0]["pageouverte"] == 1)
			return $this::USER_PAGE_OPEN; //Page open
		else
			return $this::USER_PAGE_PUBLIC; //Public page

	}

	/**
	 * Check if a user is allowed to access another user page content
	 *
	 * @param int $userID The ID of the user attempting to get user informations (0 = no user)
	 * @param int $targetUser Target user for the research
	 * @return bool TRUE if the user is allowed to see the page / FALSE else
	 */
	public function userAllowed(int $userID, int $targetUser) : bool {
		
		//Check if the requested user is the current user
		if($userID == $targetUser)
			return true; //A user can access to its own page !

		//Get the visibility level of the page
		$visibility = $this->getVisibility($targetUser);

		//Check for errors
		if($visibility == -1)
			return FALSE; //An error occured

		//Check if the page is open
		if($visibility == User::USER_PAGE_OPEN)
			return true;
		
		if($userID == 0)
			return false;
		
		if($visibility == User::USER_PAGE_PUBLIC)
			return true;
		
		if(CS::get()->components->friends->are_friend($userID, $targetUser))
			return true;
		else
			return false;

	}

	/**
	 * Check if a user can create a post on another user page
	 * 
	 * @param int $userID The ID of the user who could create post
	 * @param int $targetID The ID of the user who could receive new posts
	 * @return bool True if the user is allowed to create post / false else
	 */
	public function canCreatePosts(int $userID, int $targetID){

		//If the user is signed out, the response is NO by default
		if($userID == 0)
			return FALSE;

		//If the two user are friends, the response is yes by default
		if($userID === $targetID)
			return TRUE;
		
		//Check if the user is allowed to access user page
		if(!$this->userAllowed($userID, $targetID))
			return FALSE;
		
		//Check if the friendship of the users allow them to create posts
		if(!CS::get()->components->friends->can_post_text($userID, $targetID))
			return FALSE;
		
		//Else the user is allowed
		return TRUE;

	}
	
	/**
	 * Check whether a user allow comments on his page or not
	 * 
	 * @param int $userID The ID of the user
	 * @return bool True if comments are allowed / False else
	 */
	public function allowComments(int $userID) : bool {
		
		//Fetch the information in the database
		$conditions = "WHERE ID = ?";
		$condValues = array($userID);
		$fields = array("bloquecommentaire");

		//Perform the request
		$result = CS::get()->db->select(
			self::USER_TABLE,
			$conditions,
			$condValues,
			$fields
		);

		//Check for errors
		if(count($result) == 0)
			return FAlSE;

		//Return result
		return $result[0]["bloquecommentaire"] == 0;
	}

	/**
	 * Check whether a user allow a public access over its friends list or not
	 * 
	 * @param int $userID The ID of the user
	 * @return bool True if the friends list of the user is public / FALSE else
	 */
	public function isFriendsListPublic(int $userID) : bool {
		
		//Fetch the information in the database
		$conditions = "WHERE ID = ?";
		$condValues = array($userID);
		$fields = array("liste_amis_publique");

		//Perform the request
		$result = CS::get()->db->select(
			self::USER_TABLE,
			$conditions,
			$condValues,
			$fields
		);

		//Check for errors
		if(count($result) == 0)
			return FAlSE;

		//Return result
		return $result[0]["liste_amis_publique"] == 1;
	}

	/**
	 * Generate and return an array containing informations about a user
	 * given the database entry
	 *
	 * @param array $userInfos The user entry in the database
	 * @param bool $advanced Get advanced informations about user or not (to display its profile for example)
	 * @return array The informations ready to be returned
	 */
	private function generateUserInfosArray(array $userInfos, bool $advanced = false) : array{
		//Prepare return
		$return = array();
		$return['userID'] = $userInfos['ID'];
		$return['firstName'] = $userInfos['prenom'];
		$return['lastName'] = $userInfos['nom'];
		$return['publicPage'] = $userInfos['public'] == 1;
		$return['openPage'] = $userInfos['pageouverte'] == 1;
		$return['virtualDirectory'] = $userInfos['sous_repertoire'];

		//Add account image url
		$return['accountImage'] = CS::get()->components->accountImage->getPath($return['userID']);

		//Check if we have to fetch advanced informations
		if($advanced){
			
			//Public friend list
			$return['friend_list_public'] = $userInfos['liste_amis_publique'] == 1;

			//Personnal website
			$return['personnalWebsite'] = $userInfos['site_web'];

			//Block comment his his page ?
			$return['noCommentOnHisPage'] = $userInfos['bloquecommentaire'] == 1;

			//Allow user to post informations on his page
			$return['allowPostFromFriendOnHisPage'] = $userInfos['autoriser_post_amis'] == 1;

			//Account creation date
			$return['account_creation_time'] = strtotime($userInfos['date_creation']);

			//Add background image url
			$return['backgroundImage'] = CS::get()->components->backgroundImage->getPath($return['userID']);

			//Get the number of likes of the page
			$return['pageLikes'] = CS::get()->components->likes->count($return['userID'], Likes::LIKE_USER);

		}

		//Return result
		return $return;
	}

	/**
	 * Parse user database entry into user object
	 * 
	 * @param array $entry The database entry, as an array
	 * @param User $user The user object to populate
	 * @return User Generated User object
	 */
	public function parseDbToUser(array $entry, User $user = null) : User {

		//Create user object if required
		if($user == null)
			$user = new User;

		$user->set_id($entry['ID']);
		$user->set_firstName($entry['prenom']);
		$user->set_lastName($entry['nom']);
		$user->set_publicPage($entry['public'] == 1);
		$user->set_openPage($entry['pageouverte'] == 1);
		$user->set_virtualDirectory($entry['sous_repertoire'] == null ? "" : $entry['sous_repertoire']);
		$user->set_accountImageURL(
			CS::get()->components->accountImage->getPath($user->get_id()));
		
		//Return generated user
		return $user;
	}

	/**
	 * Parse user database entry into advanced user information object
	 * 
	 * @param array $entry The database entry, as an array
	 * @return AdvancedUser Advanced information about the user
	 */
	private function parseDbToAdvancedUser(array $entry) : AdvancedUser {

		//Parse general information about the user
		$user = $this->parseDbToUser($entry, new Advanceduser());

		$user->set_friendListPublic($entry['liste_amis_publique'] == 1);
		$user->set_personnalWebsite($entry['site_web']);
		$user->set_disallowComments($entry['bloquecommentaire'] == 1);
		$user->set_allowPostFromFriends($entry['autoriser_post_amis'] == 1);
		$user->set_creation_time(strtotime($entry['date_creation']));
		$user->set_backgroundImage(
			CS::get()->components->backgroundImage->getPath($user->get_id()));
		$user->set_pageLikes(
			CS::get()->components->likes->count($user->get_id(), Likes::LIKE_USER));

		//Return result
		return $user;

	}
}

//Register class
Components::register("user", new UserComponent());