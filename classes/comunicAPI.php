<?php
/**
 * Comunic API Server root object
 *
 * @author Pierre HUBERT
 */

class CS{

    /**
     * @var CS $instance Instance object copy
     */
    private static $instance;

    /**
     * Public constructor
     */
    public function __construct(){
        //Backup object in instance storing
        self::$instance = $this;
    }

    /**
     * Register a new child object
     *
     * @param String $name The name of the object to register
     * @param Mixed $obj The object to register
     * @return Boolean Depend of the success of the operation
     */
    public function register($name, &$obj){
        //Check if an object already exists with this name or not
        if(isset($this->{$name}))
            return false; //Conflict
        
        //Else we can register object
        $this->{$name} = &$obj;
        $this->{$name}->parent = $this;

        return true;
    }

    /**
     * Returns current active  object instance
     *
     * @return CS An instance pointing on current object
     */
    public static function &get() : CS {
        return self::$instance;
    }
}