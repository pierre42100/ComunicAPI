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
 * @return Integer $output The output (safe integer)
 */
function toInt($input){
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