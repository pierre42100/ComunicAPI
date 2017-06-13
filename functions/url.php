<?php
/**
 * URL functions
 *
 * @author Pierre HUBERT
 */


/**
 * Determine the domain of an URL
 *
 * @param String $url The URL to analyse
 * @return String The domain of the URL
 */
function get_url_domain($url){

	//First, check for "://"
	if(!preg_match("<://>", $url))
		return false;
	
	//Then split the URL
	$path = strstr($url, "://");
	$path = str_replace("://", "", $path);

	//Check if we are at the root of the domain or not
	if(!preg_match("</>", $path))
		return $path;
	
	//Else the url is a little more complex
	return explode("/", $path)[0];
}