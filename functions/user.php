<?php
/**
 * User functions
 *
 * @author Pierre HUBERT
 */

/**
 * A function that check login information are specified,
 * else it quit the scripts because of missing login
 *
 * @return bool True for a success
 */
function user_login_required() : bool {
    if(!user_signed_in()){
        Rest_fatal_error(401, "This function requires user to be logged in!");
    }

    //User logged in
    return true;
}

/**
 * Check wether the user is signed in or not
 *
 * @return bool TRUE if user is signed in / FALSE else
 */
function user_signed_in() : bool {

    //Check constant
    if(!defined("userID"))
        return false;
    
    //Check user ID
    if(userID == 0)
        return false;
    
    //User seems to be signed in
    return true;

}

/**
 * Check the validity of a password provided in a $_POST request
 * 
 * @param int $userID The ID of the user to check
 * @param string $name The name of the POST field containing the password
 * @return bool TRUE in case of success / (stop by default in case of failure)
 */
function check_post_password(int $userID, string $name) : bool {

    //Get POST field
    $password = postString($name, 2);

    //Check the password
    if(!components()->account->checkUserPassword($userID, $password))
        Rest_fatal_error(401, "The password is invalid!");
    
    //Else the password seems to be valid
    return TRUE;
}

/**
 * Update last user activity if the user allows it
 * 
 * This function do not do anything if the incognito mode
 * has been enabled by the user
 */
function update_last_user_activity_if_allowed() {

    //Check if incognito mode is enabled
    if(isset($_POST["incognito"]))
        return;
    
    //Update last activity time of the user
    CS::get()->components->user->updateLastActivity(userID);

}