<?php
/**
 * Requests functions
 *
 * @author Pierre HUBERT
 */

/**
 * Check $_POST parametres associated to a request
 *
 * @param array $varList The list of variables to check
 * @return bool True or false depending of the success of the operation
 */
function check_post_parametres(array $varList) : bool {

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
function numbers_list_to_array($list) : array {
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
 * Check a string included in a $_POST request safely.
 * This function make a REST_Error if an error occur while
 * processing the value
 * 
 * @param string $name The name of the $_POST field
 * @param int $minLength The minimal length for the string (default 1)
 * @return string The string
 */
function postString(string $name, int $minLength = 1) : string {

	//Check variable existence
	if(!isset($_POST[$name]))
		Rest_fatal_error(400, "Please add a POST string named '".$name."' in the request !");
	$value = (string) $_POST[$name];

	//Check variable length
	if(strlen($value) < $minLength)
		Rest_fatal_error(400, "Specified string in '".$name."' is too short!");

	return $value;

}

/**
 * Get a boolean given in a $_POST request safely.
 * This function make a REST_Error if an error occur while
 * processing the value
 * 
 * @param string $name The name of the $_POST field
 * @return bool The boolean
 */
function postBool(string $name) : bool {

	//Check variable existence
	if(!isset($_POST[$name]))
		Rest_fatal_error(400, "Please add a POST boolean named '".$name."' in the request !");

	return $_POST[$name] == "true";
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
 * @param string $input The string to change
 * @return string The updated string
 */
function removeHTMLnodes(string $input) : string {
	$output = str_replace("<", "&lt;", $input);
	return str_replace(">", "&gt;", $output);
}

/**
 * Check the security of an HTML string
 * 
 * @param string $input The string to check
 * @return bool TRUE if the string is safe to insert / FALSE else
 */
function checkHTMLstring(string $string) : bool {

	//Check for script or style or meta tag
	if(str_ireplace(array("<string", "<style", "<meta"), "", $string) != $string)
		return false;
	
	//Check for onclick, onkeyup, onmousehover tag
	if(str_ireplace(array("onclick", "onkeyup", "onmousehover"), "", $string) != $string)
		return false;

	//Check for images integrated to the post
	if(preg_match("/data:image/", $string))
		return false;

	//The message is valid
	return true;
}

/**
 * Check a string before inserting it
 *
 * @param string $string The string to check
 * @return bool True if the string is valid / false else
 */
function check_string_before_insert(string $string) : bool {

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

/**
 * Get the ID of a post in a rest request and check if the current
 * user is allowed to see it or not (check for basic access at least)
 * 
 * @param string $name Optionnal, the name of the post id field
 * @return int $postID The ID of the post
 */
function getPostPostIDWithAccess(string $name = "postID") : int {

	//Get the ID of the post
	$postID = getPostPostID($name);

	//Check user can see the post
	if(CS::get()->components->posts->access_level($postID, userID) === Posts::NO_ACCESS)
		Rest_fatal_error(401, "You are not allowed to access this post informations !");
	
	//Return post ID
	return $postID;

}

/**
 * Get the ID of a comment that the user is allowed to access
 * 
 * @param string $name The name of the comment field
 * @return int $commentID The ID of the comment
 */
function getPostCommentIDWithAccess($name) : int {

	//Get comment ID
	if(!isset($_POST[$name]))
		Rest_fatal_error(400, "Comment ID not specified in '".$name."'!");
	$commentID = (int) $_POST[$name];

	//Check if the comment exists
	if(!components()->comments->exists($commentID))
		Rest_fatal_error(404, "Specified comment not found!");

	//Get the ID of the associated post
	$postID = components()->comments->getAssociatedPost($commentID);

	//Check the current user can access this post
	if(CS::get()->components->posts->access_level($postID, userID) === Posts::NO_ACCESS)
		Rest_fatal_error(401, "You are not allowed to access this post informations !");

	//Return comment ID
	return $commentID;
}

/**
 * Get the ID of a movie in a rest request
 * 
 * @param string $name Optionnal, the name of the post ID field
 * @return int $movieID The ID of the movie
 */
function getPostMovieId(string $name = "movieID") : int {

	//Get movieID
	if(!isset($_POST[$name]))
		Rest_fatal_error(400, "Excepted movie ID in '".$name."' !");
	$movieID = toInt($_POST[$name]);

	//Check movie ID validity
	if($movieID < 1)
		Rest_fatal_error(400, "Invalid movie ID in '".$name."' !");
	
	//Check if the movie exists
	if(!CS::get()->components->movies->exist($movieID))
		Rest_fatal_error(404, "Specified movie does not exists!");

	return $movieID;
}

/**
 * Get a timestamp posted in a post request
 * 
 * @param string $name The name of the $_POST entry
 * @return int The timestamp if everything went well
 */
function getPostTimeStamp(string $name) : int {

	//Check if the variable exists
	if(!isset($_POST[$name]))
		Rest_fatal_error(400, "Please specify a timestamp in '".$name."' ! ");
	$timestamp = toInt($_POST[$name]);

	//Check timestamp validity
	if($timestamp < 10000)
		Rest_fatal_error(400, "Please specify a valid timestamp value in ".$timestamp." !");
	
	//Return timestamp
	return $timestamp;

}

/**
 * Check the validity of an file posted in a request
 * 
 * @param string $name The name of the $_FILES entry
 * @return bool True if the file is valid / false else
 */
function check_post_file(string $name) : bool {

	//Check if file exists
	if(!isset($_FILES[$name]))
		return false;
	
	//Check for errors
	if($_FILES[$name]['error'] != 0)
		return false;
	
	//Check if the file is empty
	if($_FILES[$name]['size'] < 1)
		return false;

	return true;

}

/**
 * Check the validity of a URL specified in a request
 * 
 * @param string $name The name of the $_POST field containing the URL
 * @return bool True if the url is valid / false else
 */
function check_post_url(string $name) : bool {

	//Check if image exists
	if(!isset($_POST[$name]))
		return false;
	$url = $_POST[$name];
	
	//Check URL
	if(!filter_var($url, FILTER_VALIDATE_URL))
		return false;

	//The URL seems to be valid
	return true;
}

/**
 * Check the validity of a Youtube video ID
 * 
 * @param string $id The ID of the YouTube video
 * @return bool True if the ID is valid / false else
 */
function check_youtube_id(string $id) : bool {

	//Check length
	if(strlen($id) < 5)
		return FALSE;
	
	//Check for illegal characters
	if($id !== str_replace(array("/", "\\", "@", "&", "?", ".", "'", '"'), "", $id))
		return FALSE;
	
	//The video is considered as valid
	return TRUE;

}

/**
 * Get some content (for post actually) from a $_POST request
 * and check its validity
 * 
 * @param string $name The name of the $_POST field
 * @return string The content of the  post
 */
function getPostContent($name){

	if(!isset($_POST[$name]))
		Rest_fatal_error(400, "Please specify some content in '"+$name+"' !");
	$content = $_POST[$name];

	//Check the security of the content
	if(!checkHTMLstring($content))
		Rest_fatal_error(400, "Your request has been rejected because it has been considered as unsecure !");

	//Return new content
	return $content;

}

/**
 * Save an image in user data directory from a POST request
 * 
 * @param string $fieldName The name of the POST field
 * @param int $userID The ID of the user making the request
 * @param string $folder The target folder in the user data directory
 * @param int $maxw The maximum width of the image
 * @param int $maxh The maximum height of the image
 * @return string The path of the image (quit the script in case of failure)
 */
function save_post_image(string $fieldName, int $userID, string $folder, int $maxw, int $maxh) : string {

	$target_userdata_folder = prepareFileCreation($userID, $folder);
	$target_file_path = $target_userdata_folder.generateNewFileName(path_user_data($target_userdata_folder, true), "png");
	$target_file_sys_path = path_user_data($target_file_path, true);

	//Try to resize, convert image and put it in its new location
	if(!reduce_image($_FILES[$fieldName]["tmp_name"], $target_file_sys_path, $maxw, $maxh, "image/png")){
		//Returns error
		Rest_fatal_error(500, "Couldn't resize sent image !");
	}

	//Return image path
	return $target_file_path;

}

/**
 * Check a user directory validity
 * 
 * @param string $directory The directory to check
 * @return bool TRUE if the domain seems to be valid / FALSE else
 */
function checkUserDirectoryValidity(string $directory) : bool {
	
	//Check domain length
	if(strlen($directory) < 4)
		return FALSE;
	
	//Check if the domain contains forbidden characters
	if(str_replace(array(".html", ".txt", ".php", "à", "â", "é", "ê", "@", "/", "\"", "'", '"', "<", ">", "?", "&", "#"), "", $directory) != $directory)
		return FALSE;

	//If we get there, the domain is valid
	return TRUE;
}

/**
 * Get a user post directory from a $_POST request and transform it to make it SQL-safe
 * 
 * @param string $name The name of the $_POST Request
 * @return string The user virtual directory, safe for saving
 * @throws RESTException If the directory is missing, or invalid
 */
function getPostUserDirectory(string $name) : string {

	//Check if the $_POST variable exists or not
	if(!isset($_POST[$name]))
		Rest_fatal_error(400, "Please specify a user directory in '".$name."'!");
	$directory = (string) $_POST[$name];

	//Check domain validity
	if(!checkUserDirectoryValidity($directory))
		Rest_fatal_error(401, "Specified directory seems to be invalid!");
	
	//Return the directory
	return $directory;

}