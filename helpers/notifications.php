<?php
/**
 * Notifications helper
 * 
 * @author Pierre HUBERT
 */

/**
 * Delete all the notifications related to a post targeting a user
 * 
 * @param int $userID Target user ID
 * @param int $postID Target post
 * @return bool TRUE in case of success / FALSE else
 */
function delete_user_notifications_other_post(int $userID, int $postID) : bool {

	$notification = new Notification();
	$notification->set_on_elem_type(Notification::POST);
	$notification->set_on_elem_id($postID);
	$notification->set_dest_user_id($userID);
	return components()->notifications->delete($notification);

}