<?php
/**
 * Account image class
 *
 * @author Pierre HUBERT
 */
class accountImage{
	/**
	 * Public constructor
	 */
	public function __construct(){
		//Nothing now
	}

	public function test(){
		return "test";
	}
}

//Register class
Components::register("accountImage", new accountImage());