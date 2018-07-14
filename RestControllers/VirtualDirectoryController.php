<?php
/**
 * Virtual directory controller
 * 
 * @author Pierre HUBERT
 */

class VirtualDirectoryController {

	/**
	 * Find a group / user using a given virtual directory
	 * 
	 * @url POST /virtualDirectory/find
	 */
	public function findVirtualDirectory(){

		//Get the virtual directory to analyze
		$virtualDirectory = getPostVirtualDirectory("directory");

		//Check if the directory is a user or group
		$userID = components()->user->findByFolder($virtualDirectory);
		$groupID = components()->groups->findByVirtualDirectory($virtualDirectory);
		
		if($userID != 0){
			$kind = "user";
			$id = $userID;
		}
		else if($groupID != 0){
			$kind = "group";
			$id = $groupID;
		}

		else
			Rest_fatal_error(404, "Specified user / group virtual directory not found !");

		return array(
			"kind" => $kind,
			"id" => $id
		);
	}

}