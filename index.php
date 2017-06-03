<?php
/**
 * Comunic Rest API
 *
 * Serves the data for users
 *
 * @author Pierre HUBERT
 */

/**
 * Page initiator
 */
include(__DIR__."/init.php");

//Include RestControllers
foreach(glob(PROJECT_PATH."RestControllers/*.php") as $restControllerFile){
	require_once $restControllerFile;
}

//Include RestServer library
require PROJECT_PATH."3rdparty/RestServer/RestServer.php";

//Allow remote requests
header("Access-Control-Allow-Origin: *");

//By default return format is json
if(!isset($_GET["format"]))
	$_GET['format'] = "json";

//Check client tokens
if($cs->config->get("site_mode") == "debug"){ //DEBUG ONLY
	$_POST['serviceName'] = "testService";
	$_POST['serviceToken'] = "testPasswd";
}
if(!$cs->tokens->checkClientRequestTokens())
	Rest_fatal_error(401, "Please check your client tokens!");

//Check if login tokens where specified
if(isset($_POST['userToken1']) AND isset($_POST['userToken2'])){
	//Try to login user
	$userID = $cs->components->user->getUserIDfromToken(APIServiceID, array(
		$_POST['userToken1'],
		$_POST['userToken2']
	));

	if($userID < 1){
		Rest_fatal_error(401, "Please check your login tokens!");
	}

	//Else save userID
	define("userID", $userID);
}
else {
	//Defined userID is number 0
	define("userID", 0);
}

/**
 * Handle Rest requests
 */
$server = new \Jacwright\RestServer\RestServer($cs->config->get("site_mode"));

//Include controllers
foreach(get_included_files() as $filePath){
	if(preg_match("<RestControllers>", $filePath)){
		$className = strstr($filePath, "RestControllers/");
		$className = str_replace(array("RestControllers/", ".php"), "", $className);
		$server->addClass($className);
	}
}

//Hanlde
$server->handle();