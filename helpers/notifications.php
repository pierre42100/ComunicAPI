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
function delete_user_notifications_over_post(int $userID, int $postID) : bool {

	user_login_required();

	$notification = new Notification();
	$notification->set_on_elem_type(Notification::POST);
	$notification->set_on_elem_id($postID);
	$notification->set_dest_user_id($userID);
	return components()->notifications->delete($notification);

}

/**
 * Delete all the notifications related to a friendship request between two
 * users
 * 
 * @param int $userOne The first user
 * @param int $userTwo The second user
 * @return bool TRUE for a success / FALSE else
 */
function delete_notifications_friendship_request(int $userOne, int $userTwo) : bool {
	
	user_login_required();

	//Create notification object
	$notification = new Notification();
	$notification->set_on_elem_type(Notification::FRIENDSHIP_REQUEST);
	$notification->set_on_elem_id(0);

	//Delete notifications in the two ways
	$notification->set_dest_user_id($userOne);
	$notification->set_from_user_id($userTwo);
	$notification->set_on_elem_id($userTwo);
	if(!components()->notifications->delete($notification))
		return false;
	
	$notification->set_dest_user_id($userTwo);
	$notification->set_from_user_id($userOne);
	$notification->set_on_elem_id($userOne);
	if(!components()->notifications->delete($notification))
		return false;

	return true;
}

/**
 * Create and push a friendship request notification
 * 
 * @param int $fromUser The ID of the user generating the notification
 * @param int $destUser The target user for the notification
 * @param string $kind The kind of notification to create
 * @return bool TRUE in case of success / FALSE else
 */
function create_friendship_notification(int $fromUser, int $destUser, string $kind) : bool {

	//Delete all the previous notifications
	if(!delete_notifications_friendship_request($fromUser, $destUser))
		return false;
	
	//Create the notification
	$notif = new Notification();
	$notif->set_time_create(time());
	$notif->set_from_user_id($fromUser);
	$notif->set_dest_user_id($destUser);
	$notif->set_on_elem_id($fromUser); //Same as from_user_id
	$notif->set_on_elem_type(Notification::FRIENDSHIP_REQUEST);
	$notif->set_type($kind);

	//Try to push the notification
	return components()->notifications->push($notif);
}