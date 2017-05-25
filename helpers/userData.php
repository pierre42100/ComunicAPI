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
 * @return String The full URL to the userdata file
 */
function path_user_data($fileURI = ""){
	return CS::get()->config->get("storage_url").$fileURI;
}