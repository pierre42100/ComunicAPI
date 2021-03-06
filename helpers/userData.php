<?php
/**
 * User data helper
 *
 * @author Pierre HUBERT
 */

/**
 * Get and returns the URL path to an userdata file
 *
 * @param string $fileURI Optionnal, defines the URI pointing on the file
 * @param bool $systemPath Optionnal, defines if system path is required instead of URL
 * @return string The full URL to the userdata file
 */
function path_user_data(string $fileURI = "", bool $systemPath = false) : string {
	if(!$systemPath)
		return CS::get()->config->get("storage_url").$fileURI;
	else
		return CS::get()->config->get("storage_path").$fileURI;
}