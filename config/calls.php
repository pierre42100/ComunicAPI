<?php
/**
 * Calls configuration file
 * 
 * @author Pierre HUBERT
 */

/**
 * Calls through clients are disabled by default.
 */
$config->set("calls", false);

/**
 * Copy the lines of code below to the overwrite.php file
 * if you would like to enable calls on your instance of
 * Comunic
 * 
 */
/*
$config->set("calls", array(
	"enabled" => true,
	"signal_server_name" => "localhost",
	"signal_server_port" => 8081,
	"stun_server" => "stun:127.0.0.1:3478",
	"turn_server" => "turn:127.0.0.1:3478",
	"turn_username" => "anonymous",
	"turn_password" => "anonymous"
));
*/