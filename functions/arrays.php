<?php
/**
 * Arrays functions
 *
 * @author Pierre HUBERT
 */

/**
 * Delete a specified value from an array
 *
 * @param array $array The array to process
 * @param Mixed $value The value to remove from the array
 * @return Boolean True for a success
 */
function array_remove_value(array &$array, $value){

	//Process value
	foreach($array as $key=>$item){
		if($item === $value)
			unset($array[$key]);
	}

	//Success
	return true;
}