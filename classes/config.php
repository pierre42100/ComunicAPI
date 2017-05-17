<?php
/**
 * Main configuration object
 *
 * @author Pierre HUBERT
 */

class config{
    
    /**
     * @var Array $config Configuration informations
     */
    private $config = array();
    
    /**
     * Public constructor
     */
    public function __construct(){
        //Does nothing now
    }

    /**
     * Set a new configuration value
     *
     * @param String $name The name of the configuration element
     * @param Mixed $value The value of the configuration element
     * @return Boolean True for a success
     */
    public function set($name, $value){
        //Set the new value
        $this->config[$name] = $value;

        //Success
        return true;
    }

    /**
     * Get a configuration value
     *
     * @param String $name The name of the required value
     * @return Mixed False if the value doesn't exists otherwise the value is returned
     */
    public function get($name){
        if(isset($this->config[$name]))
            return $this->config[$name];
        else
            return false; // The value doesn't exists
    }
}