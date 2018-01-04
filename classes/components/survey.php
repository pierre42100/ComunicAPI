<?php
/**
 * Surveys component
 * 
 * @author Pierre HUBERT
 */

class Survey {

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
	 * Get informations about a survey
	 * 
	 * @param int $postID The ID of the post related to the survey
	 * @return array Informations about the survey / empty array in case of failure
	 */
	public function get_infos(int $postID) : array {

		$survey = array();

		//Get informations about the survey
		$survey['infos'] = $this->get_survey_infos($postID);

		//Check for errors
		if(count($survey['infos']) == 0)
			return array();
		
		//Get the choices of the survey
		$survey['choices'] = $this->get_survey_choices($survey['infos']['ID']);

		//Get the choice of the user
		$survey['user_choice'] = user_signed_in() ? $this->get_user_choice($survey['infos']['ID'], userID) : 0;
		
		return $survey;
	}

	/**
	 * Get survey informations by post ID
	 * 
	 * @param int $postID The DI of the related post
	 * @return array Informations about the survey, or an empty array in case
	 * of failure
	 */
	private function get_survey_infos(int $postID) : array {

		//Fetch the database
		$conditions = "WHERE ID_texte = ?";
		$condVals = array($postID);

		$result = CS::get()->db->select($this::SURVEY_INFOS_TABLE, $conditions, $condVals);
		
		if(count($result) == 0)
			return array();

		else
			return $this->parse_survey_infos($result[0]);
	}

	/**
	 * Parse survey informations from database
	 * 
	 * @param array $db_infos Informations from the database
	 * @return array Informations about the post
	 */
	private function parse_survey_infos(array $db_infos) : array {

		$infos = array();

		//Get informations
		$infos['ID'] = $db_infos['ID'];
		$infos['userID'] = $db_infos['ID_utilisateurs'];
		$infos['postID'] = $db_infos['ID_texte'];
		$infos['creation_time'] = strtotime($db_infos['date_creation']);
		$infos['question'] = $db_infos['question'];

		return $infos;
	}

	/**
	 * Get survey choices
	 * 
	 * @param int $surveyID The ID of the target survey
	 * @return array Informations about the choices of the survey
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
			$choice_infos = $this->parse_survey_choices($row);
			$choices[$choice_infos["choiceID"]] = $choice_infos;
		}

		return $choices;

	}

	/**
	 * Parse survey choices from database
	 * 
	 * @param array $db_infos Informations about the choice
	 * @return array Usable informations about the surveay
	 */
	private function parse_survey_choices(array $db_infos) : array {
		
		$infos = array();

		$infos['choiceID'] = $db_infos['ID'];
		$infos['name'] = $db_infos['Choix'];
		$infos['responses'] = $this->count_choices_responses($infos['choiceID']);

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
Components::register("survey", new Survey());