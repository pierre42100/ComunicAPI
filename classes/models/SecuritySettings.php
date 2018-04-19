<?php
/**
 * Security settings model
 * 
 * @author Pierre HUBERT
 */

class SecuritySettings extends BaseUserModel {

	//Private fields
	private $security_question_1;
	private $security_answer_1;
	private $security_question_2;
	private $security_answer_2;


	//Set and get first security question
	public function set_security_question_1(string $security_question_1){
		$this->security_question_1 = $security_question_1 == "" ? null : $security_question_1;
	}

	public function has_security_question_1() : bool {
		return $this->security_question_1 != null;
	}

	public function get_security_question_1() : string {
		return $this->security_question_1 != null ? $this->security_question_1 : "null";
	}


	//Set and get first security answer
	public function set_security_answer_1(string $security_answer_1){
		$this->security_answer_1 = $security_answer_1 == "" ? null : $security_answer_1;
	}

	public function has_security_answer_1() : bool {
		return $this->security_answer_1 != null;
	}

	public function get_security_answer_1() : string {
		return $this->security_answer_1 != null ? $this->security_answer_1 : "null";
	}


	//Set and get second security question
	public function set_security_question_2(string $security_question_2){
		$this->security_question_2 = $security_question_2 == "" ? null : $security_question_2;
	}

	public function has_security_question_2() : bool {
		return $this->security_question_2 != null;
	}

	public function get_security_question_2() : string {
		return $this->security_question_2 != null ? $this->security_question_2 : "null";
	}


	//Set and get second security answer
	public function set_security_answer_2(string $security_answer_2){
		$this->security_answer_2 = $security_answer_2 == "" ? null : $security_answer_2;
	}

	public function has_security_answer_2() : bool {
		return $this->security_answer_2 != null;
	}

	public function get_security_answer_2() : string {
		return $this->security_answer_2 != null ? $this->security_answer_2 : "null";
	}

}