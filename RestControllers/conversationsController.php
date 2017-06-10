<?php
/**
 * Conversations controller
 *
 * @author Pierre HUBERT
 */

class conversationsController{

	/**
	 * Create a new conversation
	 *
	 * @url POST /conversations/create
	 */
	public function createConversation(){
		user_login_required();

		//Check for parametres
		if(!check_post_parametres(array("name", "follow", "users")))
			Rest_fatal_error(501, "Please check parametres passed with the request !");
		
		//Extract parametres
		$conversationName = ($_POST["name"] == "false" ? false : $_POST['name']);
		$followConversation = ($_POST['follow'] == "true" ? true : false);
		$users = users_list_to_array($_POST['users']);

		//Check users
		if(count($users) < 1)
			Rest_fatal_erro(501, "Please select at least one user !");
		
		//Perform the request
		
	}

}