<?php
/**
 * Comunic Server object helper
 * 
 * @author Pierre HUBERT
 */

/**
 * Get root Comunic object
 * 
 * @return CS Comunic Server object
 */
function cs() : CS {
	return CS::get();
}