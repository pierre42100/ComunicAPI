<?php
/**
 * Language settings base model
 * 
 * @author Pierre HUBERT
 */

class LanguageSettings extends BaseUserModel {

	//Available languages
	const LANGUAGES = array("fr", "en");

	//Private fields
	private $lang;

	//Set and get the language the user
	public function set_lang(string $lang){
		$this->lang = $lang;
	}

	public function has_lang() : bool {
		return $this->lang != null;
	}

	public function get_lang() : string {
		return $this->lang;
	}
}