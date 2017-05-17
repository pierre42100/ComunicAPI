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

//By default format is json
if(!isset($_GET["format"]))
    $_GET['format'] = "json";

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