<?php
/**
 * Surveys component
 * 
 * @author Pierre HUBERT
 */

class SurveyComponent {

	/**
	 * Survey infos table
	 */
	const SURVEY_INFOS_TABLE = "sondage";

	/**
	 * Survey choices table
	 */
	const SURVEY_CHOICES_TABLE = "sondage_choix";

	/**
	 * Survey responses table
	 */
	const SURVEY_RESPONSE_TABLE = "sondage_reponse";

	/**
	 * Create a new survey
	 * 
	 * @param int $postID The target post for the survey
	 * @param int $userID The ID of the user creating the survey
	 * @param string $question The question for the survey
	 * @param array $answers The answers for the survey
	 * @return bool True for a sucess / False for a failure
	 */
	public function create(int $postID, int $userID, string $question, array $answers) : bool {

		//Create the main survey informations
		$main_infos = array(
			"ID_utilisateurs" => $userID,
			"ID_texte" => $postID,
			"date_creation" => mysql_date(),
			"question" => $question
		);

		//Try to create survey main informations table
		if(!CS::get()->db->addLine($this::SURVEY_INFOS_TABLE, $main_infos))
			return false;
		
		//Get the ID of the survey
		$surveyID = CS::get()->db->getLastInsertedID();

		//Check for errors
		if($surveyID < 1)
			return false;
		
		//Process each answer
		$answers_data = array();
		foreach($answers as $line){
			$answers_data[] = array(
				"ID_sondage" => $surveyID,
				"date_creation" => mysql_date(),
				"Choix" => $line
			);
		}

		//Insert all the answers
		CS::get()->db->addLines($this::SURVEY_CHOICES_TABLE, $answers_data);

		//Success
		return true;

	}

	/**
	 * Get informations about a survey
	 * 
	 * @param int $postID The ID of the post related to the survey
	 * @return Survey Informations about the survey / invalid Survey in case of failure
	 */
	public function get_infos(int $postID) : Survey {

		//Get informations about the survey
		$survey = $this->get_survey_infos($postID);

		//Check for errors
		if(!$survey->isValid())
			return new Survey();

		//Get the choice of the user
		$survey->set_user_choice(user_signed_in() ? $this->get_user_choice($survey->get_id(), userID) : 0);
		
		//Get the choices of the survey
		$survey->set_choices($this->get_survey_choices($survey->get_id()));
		
		return $survey;
	}

	/**
	 * Check wether there is a survey associated to a post
	 * 
	 * @param int $postID The ID of the post to check
	 * @return bool TRUE if a survey exists / FALSE else
	 */
	public function exists(int $postID) : bool {
		return CS::get()->db->count($this::SURVEY_INFOS_TABLE, "WHERE ID_texte = ?", array($postID)) > 0;
	}

	/**
	 * Check wether a choice exists and is attached to a survey
	 * 
	 * @param int $surveyID the ID of the survey
	 * @param int $choiceID The ID of the choice to check
	 * @return bool TRUE if a choice exists and is valid / FALSE else
	 */
	public function choice_exists(int $surveyID, int $choiceID) : bool {
		return CS::get()->db->count(
			$this::SURVEY_CHOICES_TABLE, 
			"WHERE ID_sondage = ? AND ID = ?", 
			array($surveyID, $choiceID)) > 0;
	}

	/**
	 * Get the ID of a survey associated to a post
	 * 
	 * @param int $postID The ID of the post
	 * @return int The ID of the associated survey / 0 if none was found
	 */
	public function get_id(int $postID) : int {

		//Perform a request on the database
		$tableName = $this::SURVEY_INFOS_TABLE;
		$conditions = "WHERE ID_texte = ?";
		$values = array($postID);
		$fields = array("ID");
		$results = CS::get()->db->select($tableName, $conditions, $values, $fields);

		//Check for results
		if(count($results) == 0)
			return 0;
		
		return $results[0]["ID"];
	}

	/**
	 * Send a response to a survey
	 * 
	 * @param int $userID The ID of the user giving a response to the survey
	 * @param int $surveyID The ID of the target survey
	 * @param int $choiceID The ID of the selected choice
	 * @return bool TRUE for a success / FALSE for a failure
	 */
	public function send_response(int $userID, int $surveyID, int $choiceID){
		
		//Generate the new data line
		$data = array(
			"ID_utilisateurs" => $userID,
			"ID_sondage" => $surveyID,
			"ID_sondage_choix" => $choiceID,
			"date_envoi" => mysql_date()
		);

		//Try to save response
		return CS::get()->db->addLine($this::SURVEY_RESPONSE_TABLE, $data);
	}

	/**
	 * Cancel the response of a user to a survey
	 * 
	 * @param int $surveyID The ID of the target survey
	 * @param int $userID The ID of the user removing his response
	 * @return bool FALSE in case of failure / TRUE in case of success
	 */
	public function cancel_response(int $surveyID, int $userID) : bool {
		
		//Perform a request on the database
		return CS::get()->db->deleteEntry(
			$this::SURVEY_RESPONSE_TABLE, 
			"ID_sondage = ? AND ID_utilisateurs = ?", 
			array($surveyID, $userID));
	}

	/**
	 * Delete the survey associated to a post
	 * 
	 * @param int $postID The ID of the post associated with the survey
	 * @return bool TRUE in case of success / FALSE else
	 */
	public function delete(int $postID) : bool {

		//Get the ID of the survey to delete
		$surveyID = $this->get_id($postID);

		//Check for errors
		if($surveyID == 0)
			return false;

		//Delete informations from the responses table
		CS::get()->db->deleteEntry($this::SURVEY_RESPONSE_TABLE, "ID_sondage = ?", array($surveyID));

		//Delete informations from the choices table
		CS::get()->db->deleteEntry($this::SURVEY_CHOICES_TABLE, "ID_sondage = ?", array($surveyID));

		//Delete informations from the informations table
		CS::get()->db->deleteEntry($this::SURVEY_INFOS_TABLE, "ID = ?", array($surveyID));
		
		//Success
		return true;

	}

	/**
	 * Get survey informations by post ID
	 * 
	 * @param int $postID The DI of the related post
	 * @return Survey Information about the survey, or an invalid object in case
	 * of failure
	 */
	private function get_survey_infos(int $postID) : Survey {

		//Fetch the database
		$conditions = "WHERE ID_texte = ?";
		$condVals = array($postID);

		$result = CS::get()->db->select($this::SURVEY_INFOS_TABLE, $conditions, $condVals);
		
		if(count($result) == 0)
			return new Survey();

		else
			return $this->dbToSurvey($result[0]);
	}

	/**
	 * Turn survey entry from the database into Survey object
	 * 
	 * @param array $data Data from the database
	 * @return Survey Generated survey object
	 */
	private function dbToSurvey(array $data) : Survey {

		$survey = new Survey();

		$survey->set_id($data['ID']);
		$survey->set_userID($data["ID_utilisateurs"]);
		$survey->set_postID($data["ID_texte"]);
		$survey->set_time_sent(strtotime($data['date_creation']));
		$survey->set_question($data['question']);

		return $survey;
	}

	/**
	 * Get survey choices
	 * 
	 * @param int $surveyID The ID of the target survey
	 * @return array Informations about the choices of the survey (as Survey objects)
	 */
	private function get_survey_choices(int $surveyID) : array {

		//Fetch the database
		$conditions = "WHERE ID_sondage = ?";
		$values = array($surveyID);

		//Fetch the questions
		$result = CS::get()->db->select($this::SURVEY_CHOICES_TABLE, $conditions, $values);

		//Parse results
		$choices = array();
		foreach($result as $row){
			$choice_infos = $this->dbToChoice($row);
			$choices[$choice_infos->get_id()] = $choice_infos;
		}

		return $choices;

	}

	/**
	 * Parse survey choices from database
	 * 
	 * @param array $db_infos Informations about the choice
	 * @return SurveyChoice Usable informations about the survey
	 */
	private function dbToChoice(array $db_infos) : SurveyChoice {
		
		$infos = new SurveyChoice();

		$infos->set_id($db_infos['ID']);
		$infos->set_name($db_infos['Choix']);
		$infos->set_responses($this->count_choices_responses($infos->get_id()));

		return $infos;

	}

	/**
	 * Count the number of responses for a choice
	 * 
	 * @param int $choiceID The ID of the choice
	 * @return int The number of response
	 */
	private function count_choices_responses(int $choiceID) : int {
		
		//Perform a request on the database
		$conditions = "WHERE ID_sondage_choix = ?";
		$values = array($choiceID);

		return CS::get()->db->count($this::SURVEY_RESPONSE_TABLE, $conditions, $values);
	}

	/**
	 * Get the user choice for a survey
	 * 
	 * @param int $surveyID The ID of the target survey
	 * @param int $userID The ID of the target user
	 * @return int The ID of the selected answer, or 0 if no response was given
	 */
	private function get_user_choice(int $surveyID, int $userID) : int {

		//Perform a request on the database
		$conditions = "WHERE ID_utilisateurs = ? AND ID_sondage = ?";
		$values = array($userID, $surveyID);
		$result = CS::get()->db->select($this::SURVEY_RESPONSE_TABLE, $conditions, $values);

		//Check if no response was given
		if(count($result) == 0)
			return 0;

		return $result[0]["ID_sondage_choix"];

	}
}

//Register component
Components::register("survey", new SurveyComponent());