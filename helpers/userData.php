<?php
/**
 * User data helper
 *
 * @author Pierre HUBERT
 */

/**
 * Get and returns the URL path to an userdata file
 *
 * @param String $fileURI Optionnal, defines the URI pointing on the file
 * @param Boolean $systemPath Optionnal, defines if system path is required instead of URL
 * @return String The full URL to the userdata file
 */
function path_user_data($fileURI = "", $systemPath = false){
	if(!$systemPath)
		return CS::get()->config->get("storage_url").$fileURI;
	else
		return CS::get()->config->get("storage_path").$fileURI;
}

/**
 * Get and return the URL path to a specified account image
 *
 * @param String $imageURI Optionnal, defines URI of the image
 * @param Boolean $systemPath Optionnal, defines if system path is required instead of URL
 * @return String The full URL to the image account file
 */
function path_account_image($imageURI="", $systemPath = false){
	return path_user_data(CS::get()->config->get("imageAccountPath").$imageURI, $systemPath);
}