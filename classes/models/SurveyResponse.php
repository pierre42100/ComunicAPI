<?php
/**
 * Survey response object
 * 
 * @author Pierre HUBERT
 */

class SurveyResponse extends BaseUniqueObjectFromUser {

	//Private fields
	private $surveyID;
	private $choiceID;

	//Set and get survey ID
	public function set_surveyID(int $surveyID){
		$this->surveyID = $surveyID;
	}

	public function has_surveyID() : bool {
		return $this->surveyID > 0;
	}

	public function get_surveyID() : int {
		return $this->surveyID;
	}

	//Set and get choice ID
	public function set_choiceID(int $choiceID){
		$this->choiceID = $choiceID;
	}

	public function has_choiceID() : bool {
		return $this->choiceID > 0;
	}

	public function get_choiceID() : int {
		return $this->choiceID;
	}

}