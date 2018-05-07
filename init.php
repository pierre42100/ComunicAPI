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

//Include helpers
foreach(glob(PROJECT_PATH."helpers/*.php") as $funcFile){
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
define("DBprefix", $cs->config->get("dbprefix"));
unset($db);

//Add token object
$clients = new APIClients();
$cs->register("clients", $clients);
unset($clients);

//Include models
foreach(glob(PROJECT_PATH."classes/models/*.php") as $classFile){
    require_once $classFile;
}

//Include components
foreach(glob(PROJECT_PATH."classes/components/*.php") as $classFile){
    require_once $classFile;
}

//Add components object
$components = new Components();
$cs->register("components", $components);
unset($components);