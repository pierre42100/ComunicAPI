<?php
/**
 * Restserver controller
 * 
 *  Surveys controller
 * 
 * @author Pierre HUBERT
 */

class SurveysController {

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

	/**
	 * Parse a survey object into a valid API entry
	 * 
	 * @param Survey $survey The survey object to convert
	 * @return array Generated API entry
	 */
	public static function SurveyToAPI(Survey $survey) : array {

		$data = array();

		$data["ID"] = $survey->get_id();
		$data["userID"] = $survey->get_userID();
		$data["postID"] = $survey->get_postID();
		$data["creation_time"] = $survey->get_time_sent();
		$data["question"] = $survey->get_question();
		$data["user_choice"] = $survey->get_user_choice();
		
		//Process survey choices
		$data["choices"] = array();
		
		foreach($survey->get_choices() as $choice)
			$data["choices"][$choice->get_id()] = self::SurveyChoiceToAPI($choice);

		return $data;
	}

	/**
	 * Turn a SurveyChoice object into an API array
	 * 
	 * @param SurveyChoice $choice The choice to convert
	 * @return array Generated array
	 */
	public static function SurveyChoiceToAPI(SurveyChoice $choice) : array {

		$data = array();

		$data["choiceID"] = $choice->get_id();
		$data["name"] = $choice->get_name();
		$data["responses"] = $choice->get_responses();

		return $data;

	}

}