<?php
/**
 * Page initiator
 *
 * @author Pierre HUBERT
 */

//Define the base of the project
define("PROJECT_PATH", __DIR__."/");

//Include classes
foreach(glob(PROJECT_PATH."classes/*.php") as $classFile){
    require_once $classFile;
}

//Include functions
foreach(glob(PROJECT_PATH."functions/*.php") as $funcFile){
    require_once $funcFile;
}

//Create root object
$cs = new CS();

//Create configuration element
$config = new config();
$cs->register("config", $config);

//Include configuration
foreach(glob(PROJECT_PATH."config/*.php") as $confFile){
	require $confFile;
}
unset($config);

//Connexion to the database
$db = new DBLibrary(($cs->config->get("site_mode") == "debug"));
$cs->register("db", $db);
$db->openMYSQL($cs->config->get('mysql')['host'], 
        $cs->config->get('mysql')['user'], 
        $cs->config->get('mysql')['password'], 
        $cs->config->get('mysql')['database']);
unset($db);

//Add token object
$tokens = new Tokens();
$cs->register("tokens", $tokens);
unset($tokens);

//Add user object
$user = new User();
$cs->register("user", $user);
unset($user);