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
 * @return Boolean True for a success
 */
function user_login_required() : bool{
    if(!user_signed_in()){
        Rest_fatal_error(401, "This function requires user to be logged in!");
    }

    //User logged in
    return true;
}

/**
 * Check wether the user is signed in or not
 *
 * @return TRUE if user is signed in / FALSE else
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