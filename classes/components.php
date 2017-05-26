<?php
/**
 * Components unificator
 *
 * @author Pierre HUBERT
 */
class Components {

	/**
	 * A static table that contains all the object
	 *
	 * @var array
	 */
	private static $objects = array();

	/**
	 * Public constructor
	 */
	public function __construct(){
		//Transform static object into dynamic object
		foreach(self::$objects as $name=>$object)
			$this->{$name} = $object;
	}

	/**
	 * Class registration
	 *
	 * @param String $className The name of the class to register
	 * @param Object $object The object to register
	 */
	public static function register($className, $object){
		self::$objects[$className] = $object;
	}
}