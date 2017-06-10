<?php
/**
 * Conversations component
 *
 * @author Pierre HUBERT
 */

class conversations {

	/**
	 * Create a new conversation
	 *
	 * @param Integer $userID The ID of the user creating the conversation
	 * @param Boolean $follow Defines if the user creating the conversation will follow it 
	 * @param Array $usersList The list of users following the conversation
	 * @param Mixed $name Optionnal, the name of the conversation
	 */

}

//Register component
Components::register("conversations", new conversations());