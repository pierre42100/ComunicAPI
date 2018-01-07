<?php
/**
 * URL analyzer
 * 
 * Retreivee the source code of a webpage to extracts
 * its open graph tags
 * 
 * @author Pierre HUBERT
 */

class URLAnalyzer {

	/**
	 * Analyze a given URL to extract OpenGraph content
	 * 
	 * @param string $url The URL to analyze
	 * @param int $timeout The
	 * @return array Open graph informations in case of success or
	 * empty array in case of failure
	 */
	public static function analyze(string $url, int $timeout = 15) : array {

		//Initialize curl
		$ch = curl_init($url);
		
		//Set timeout
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		
		//Get the response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		//Perform the request
		$source = curl_exec($ch);
		if(!$source)
			return array();
		
		//Analyze response
		preg_match_all('#<meta (.*?)>#is', $source, $results, PREG_PATTERN_ORDER);

		//Process results
		$list = array();
		foreach($results[1] as $entry){

			//Remove spaces
			$entry = str_replace(array(" =", "= "), "=", $entry);

			//Check if the meta tag represent an interest
			if(preg_match("/property/", $entry) AND preg_match("/og:/", $entry)){

				//Search for property tag and content tag
				preg_match("#property=[\"\']og:(.*?)[\"\']#is", $entry, $matches1);
				preg_match("#content=[\"\'](.*?)[\"\']#is", $entry, $matches2);

				$name = isset($matches1[1]) ? $matches1[1] : 1;
				$value = isset($matches2[1]) ? $matches2[1] : "";
				$list[$name] = $value;
			}
		}


		return $list;
	}

}