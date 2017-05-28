<?php
/**
 * Friends component
 *
 * @author Pierre HUBERT
 */

class friends {

	/**
	 * Public construcor
	 */
	public function __construct(){
		//Nothing now
	}

}

//Register component
Components::register("friends", new friends());