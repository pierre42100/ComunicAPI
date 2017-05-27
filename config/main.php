<?php
/**
 * Main configuration file
 *
 * @author Pierre HUBERT
 */

/**
 * The mode of the website
 * debug / production
 */
$config->set("site_mode", "debug");

/**
 * Storage URL
 *
 * The base url of the user_data folder 
 */
$config->set("storage_url", "http://devweb.local/comunic/current/user_data/");

/**
 * Storage PATH
 *
 * The path (on the server) pointing of the user_data folder
 */
$config->set("storage_path", "/home/pierre/Documents/projets_web/comunic/current/user_data/");

/**
 * Database credentials
 *
 * The database must be a MySQL database
 */
$config->set("mysql", array(
    "host" => "localhost",
    "database" => "comunic", 
    "user" => "root", 
    "password" => "root"
));

/**
 * Tables of database prefix
 *
 * For new tables only
 */
$config->set("dbprefix", "comunic_");