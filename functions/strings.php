<?php
/**
 * Strings methods
 *
 * @author Pierre HUBERT
 */

/**
 * Generate a random string, using a cryptographically secure 
 * pseudorandom number generator (random_int)
 * 
 * @param int $length      How many characters do we want?
 * @param string $keyspace A string of all possible characters
 *                         to select from
 * @return string
 */
function random_str(int $length, string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}

/**
 * Get the current date formatted for the "datetime" object of
 * a Mysql database
 * 
 * @return string Formatted string
 */
function mysql_date() : string {
    return date("Y-m-d h:i:s", time());
}