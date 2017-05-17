<?php
/**
 * Changes Rest Controller
 *
 * @author Pierre HUBERT
 */
class changesController {
	/**
	 * Get the changes of a specified period
	 * 
	 * @url GET /changes/get/$from/$to
	 */
	public function getChanges($from, $to){

		//Check values
		if($from*1 > $to*1)
			Rest_fatal_error(401, "Please specify a valid interval !");


		//We try to get changes of the specified period
		$changes = DW::get()->changes->get($from*1, $to*1);
		if($changes === false)
			Rest_fatal_error(500, "Couldn't get changes of the specified period !");
		
		//Return the informations
		return $changes;
	}
}