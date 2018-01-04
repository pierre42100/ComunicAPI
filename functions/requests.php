<?php
/**
 * Requests functions
 *
 * @author Pierre HUBERT
 */

/**
 * Check $_POST parametres associated to a request
 *
 * @param Array $varList The list of variables to check
 * @return Boolean True or false depending of the success of the operation
 */
function check_post_parametres(array $varList){

	//Check each fields
	foreach($varList as $process){

		//Check variable existence
		if(!isset($_POST[$process]))
			return false; //The variable does not exists
		
		//Check variable content
		if($_POST[$process] == "")
			return false; //The variable is empty

	}

	//If we arrive there, it is a success
	return true;
}

/**
 * Convert a list of numbers (anything with IDs) comma-separated to an array
 *
 * @param String $list The input list
 * @return Array The list of user / an empty list in case of errors
 */
function numbers_list_to_array($list) : array{
	//Split the list into an array
	$array = explode(",", $list);
	$usersList = array();

	foreach($array as $process){

		//Check the entry is valid
		if(toInt($process) < 1)
			//return array();
			continue; //Ignore entry
		
		//Add the entry to the list
		$usersList[toInt($process)] = toInt($process);
	}

	//Return the result
	return $usersList;
}

/**
 * Securely transform user given number (mixed) to integer (int)
 *
 * @param Mixed $input The input variable (mixed)
 * @return int $output The output (safe integer)
 */
function toInt($input) : int{
	return floor($input*1);
}

/**
 * Remove HTML markup codes (<, >)
 *
 * @param String $input The string to change
 * @return String The updated string
 */
function removeHTMLnodes($input){
	$output = str_replace("<", "&lt;", $input);
	return str_replace(">", "&gt;", $output);
}

/**
 * Check a string before inserting it
 *
 * @param String $string The string to check
 * @return Boolean True if the string is valid / false else
 */
function check_string_before_insert($string){

	//First, empty string are invalid
	if($string == "")
		return false;
	
	//Remove HTML tags before continuing
	$string = str_replace(array("<", ">"), "", $string);

	//Check string size
	if(strlen($string)<3)
		return false;

	//Check if the string has at least three different characters
	if(strlen(count_chars($string,3)) < 3)
		return false; 
	
	//Success
	return true;
}

/**
 * Make a string safe to be used to perform a query on a database
 *
 * @param string $input The string to process
 * @return string The result string
 */
function safe_for_sql(string $input) : string {

	//Perform safe adapation
	$input = str_ireplace("\\", "\\\\", $input);
	$input = str_ireplace("'", "\\'", $input);
	$input = str_ireplace('"', "\\\"", $input);

	return $input;

}

/**
 * Check a given user ID
 * 
 * @param int $userID The user ID to check
 * @return bool True if userID is valid, false else
 */
function check_user_id(int $userID) : bool {

	if($userID < 1)
		return false; //Invalid

	return true; //Valid
}

/**
 * Get userID posted in a request and return it if there
 * isn't any error
 * 
 * @param string $name Optionnal, the name of the post field
 * @return int User ID
 * @throws RestError in case of error
 */
function getPostUserID(string $name = "userID") : int {

	//Get userID post
	if(!isset($_POST[$name]))
		Rest_fatal_error(400, "Please specify a userID in '".$name."' !");
	
	$userID = toInt($_POST[$name]);

	//Check userID validity
	if(!check_user_id($userID))
		Rest_fatal_error(400, "Invalid userID in '".$name."' !");
	
	//Check if user exits
	if(!CS::get()->components->user->exists($userID))
		Rest_fatal_error(404, "Specified user in '".$name."' not found !");
	
	return $userID;
}

/**
 * Get the ID of a conversation posted in a request and return
 * if it is a valid ID
 * 
 * @param string $name Optionnal, the name of the post field
 * @return int $convID The ID of the conversation
 */
function getPostConversationID(string $name = "conversationID") : int {

	//Get conversationID
	if(!isset($_POST[$name]))
		Rest_fatal_error(400, "Excepted conversation ID in '".$name."' !");
	$conversationID = toInt($_POST[$name]);

	//Check conversationID validity
	if($conversationID < 1)
		Rest_fatal_error(400, "Invalid conversation ID !");
	
	//Check if conversation exists
	if(!CS::get()->components->conversations->exist($conversationID))
		Rest_fatal_error(404, "Specified conversation not found!");
	
	return $conversationID;

}

/**
 * Get the ID of a post in a rest request
 * 
 * @param string $name Optionnal, the name of the post id field
 * @return int $postID The ID of the post
 */
function getPostPostID(string $name = "postID") : int {

	//Get postID
	if(!isset($_POST[$name]))
		Rest_fatal_error(400, "Excepted post ID in '".$name."' !");
	$postID = toInt($_POST[$name]);

	//Check post ID validity
	if($postID < 1)
		Rest_fatal_error(400, "Invalid post ID!");
	
	//Check if the post exists
	if(!CS::get()->components->posts->exist($postID))
		Rest_fatal_error(404, "Specified post does not exists!");
	
	return $postID;
}