<?php
/**
 * Database helper
 * 
 * @author Pierre HUBERT
 */

/**
 * Get and return database object
 * 
 * @param DBLibrary The database object
 */
function db() : DBLibrary {
    return CS::get()->db;
}