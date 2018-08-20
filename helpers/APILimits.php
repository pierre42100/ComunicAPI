<?php
/**
 * API Limits helper
 * 
 * @author Pierre HUBERT
 */

/**
 * Limit the number of time a query can be performed by a client
 * 
 * @param string $name The name of the action to limit
 * @param bool $trigger Count this as an action of the user or not
 */
function api_limit_query(string $name, bool $trigger){
	cs()->limit->limit_query($name, $trigger);
}