<?php
/**
 * Restserver controller
 * 
 *  Surveys controller
 * 
 * @author Pierre HUBERT
 */

class surveysController {

	/**
	 * Cancel a response to a survey
	 * 
	 * @url POST /surveys/cancel_response
	 */
	public function cancel_response(){

		user_login_required();

		//Get the ID of the survey
		$surveyID = $this->getSurveyIDFromPostID("postID");

		//Try to cancel the user's response to the survey
		if(!components()->survey->cancel_response($surveyID, userID))
			Rest_fatal_error(500, "Couldn't cancel user response to the survey !");
		
		//Success
		return array("success" => "The response to the survey was cancelled!");

	}

	/**
	 * Send a response to a survey
	 * 
	 * @url POST /surveys/send_response
	 */
	public function send_response(){

		user_login_required();

		//Get the ID of the survey
		$surveyID = $this->getSurveyIDFromPostID("postID");

		//Get the ID of the response of the user
		if(!isset($_POST['choiceID']))
			Rest_fatal_error(401, "Please specify the ID of your choice in your request!");
		$choiceID = (int) $_POST['choiceID'];

		//Try to cancel the user's previous response to the survey
		if(!components()->survey->cancel_response($surveyID, userID))
			Rest_fatal_error(500, "Couldn't cancel user previous response to the survey !");
		
		//Check if the user choice exists or not
		if(!components()->survey->choice_exists($surveyID, $choiceID))
			Rest_fatal_error(404, "Specified response does not exists!");
		
		//Save the answer
		if(!components()->survey->send_response(userID, $surveyID, $choiceID))
			Rest_fatal_error(500, "An error occured while trying to save response to the survey!");
		
		//Success
		return array("success" => "User response has been saved!");
	}

	/**
	 * Get the ID of a survey from a $_POST ID
	 * 
	 * @param string $name The name of the post field that contains
	 * associated POST ID
	 * @return int The ID of the target survey
	 */
	private function getSurveyIDFromPostID(string $name) : int {

		//Get the ID of the target post
		$postID = getPostPostIDWithAccess("postID");

		//Check if a survey is associated with the post
		$surveyID = components()->survey->get_id($postID);

		//Check for errors
		if($surveyID == 0)
			Rest_fatal_error(401, "No survey was found with the specified post !");
		
		//Return survey ID
		return $surveyID;

	}

}