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
function user_login_required(){
    if(!defined("userID")){
        Rest_fatal_error(401, "This function requires user to be logged in!");
    }

    //User logged in
    return true;
}