<?php
/**
 * Components helper
 *
 * @author Pierre HUBERT
 */

/**
 * Easily get the component object
 * 
 * @return Components The components object
 */
function components() : Components {
	return CS::get()->components;
}