<?php
/*
 * The MIT License
 *
 * Copyright 2016 Pierre HUBERT.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * DB access and R/W
 * Currently supports the following DataBases types :
 *  - sqlite
 *  - mysql
 * 
 * If you would like to add support for different services, dont hesitate to 
 * participate.
 *
 * @author Pierre HUBERT
 */
class DBLibrary {
    
    /**
     * @var Boolean $connected Say if we are already connected or not.
     */
    private $connected = false;
    
    /**
     * @var PDO $db The DataBase ojbect
     */
    private $db;
    
    /**
     * @var Boolean Enable or not verbosing mode
     */
    private $verbose = false;
    
    /**
     * Class constructor
     * 
     * @param Boolean $verbose Enable or not the inclusion of SQL requests in Exceptions
     */
    public function __construct($verbose = false){
        //Saving verbose mode
        $this->verbose = $verbose;
    }
    
    /**
     * Open a MySQL database
     * 
     * @param String $host MySQL Server Name
     * @param String $username MySQL username
     * @param String $password MySQL password
     * @param String $nameDB Name of the DataBase
     */
    public function openMYSQL($host, $username, $password, $nameDB){
        //Generating PDO params
        $pdoParams = "mysql:host=".$host.";dbname=".$nameDB;
        $credentials = array(
            "username" => $username,
            "password" => $password
        );
        
        //Opening DataBase
        $this->openDB($pdoParams, $credentials);
    }
    
    /**
     * Open a SQLite DataBase
     * 
     * @param String $pathToDB The path to SQLITE DB
     * @return nothing
     */
    public function openSQLite($pathToDB){
        //We check the type of file if it exists
        if(file_exists($pathToDB)){
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if(finfo_file($finfo, $pathToDB) != "application/octet-stream"){
                exit("Error: Trying to open a "
                        . "non-application/octet-stream type file !");
            }
        }
        
        //Generating PDO params
        $pdoParams = "sqlite:".$pathToDB;
        
        //Opening DataBase
        $this->openDB($pdoParams);
    }
    
    /**
     * Open a database (generic function)
     *
     * Use this for debugging :
     * $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
     * 
     * @param String $pdoParams Informations about the DataBase to open
     * @param Array $credentials The username and password (optionnal)
     * @return nothing
     */
    private function openDB($pdoParams, array $credentials = array()){
        try{
            //We check if any DB is already opened
            if($this->checkOpenDB()){
                //We run into an error
                throw new Exception("Trying to open a database "
                . "while another is already opened !");
            }

            //We open DataBase
            if(count($credentials) == "")
                $this->db = new PDO($pdoParams);
            else
                $this->db = new PDO($pdoParams, 
                        $credentials['username'],
                        $credentials['password']);
        }
        catch (Exception $e){
            exit($this->echoException($e));
        }
        catch(PDOException $e){
            exit($this->echoPDOException($e));
        }
        
        //We set the connected var to yes
        $this->connected = true;

        //We set PDO to return errors in verbose mode
        if($this->verbose)
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    /**
     * Return the DataBase Object
     * 
     * @return PDO  The DataBase object
     */
    public function getDBobject(){
        return $this->db;
    }
    
    /**
     * Check if another DB is already opened
     * 
     * @return Boolean True or false depending of the opened state
     */
    private function checkOpenDB(){
        if($this->connected){
           return true;
        }
        
        //No database opened yet
        return false;
    }
    
    /**
     * Execute SQL code to the DataBase
     * 
     * @param  String $sql The SQL to execute
     * @return Integer Number of lines affected
     */
    public function execSQL($sql){
        //We check if any database is opened
        if (!$this->checkOpenDB()) {
            return false;
        }

        //We try to perform the task
        try{
            return $this->db->exec($sql);
        } catch (PDOException $e) {
            exit($this->echoPDOException($e));
        }
    }
    
    /**
     * Add a line to a table of the database
     * 
     * @param String $tableName The name of the table
     * @param Array $values The fields values
     * @return Boolean True or false depending of the success of the operation
     */
    public function addLine($tableName, array $values){
        //We try to perform the task
        try{
            //We check if any database is opened
            if (!$this->checkOpenDB()) {
                throw new Exception("There isn't any opened DataBase !");
            }
        
        
            //Generating SQL command
            $sql = "INSERT INTO ".$tableName." "; 

            //Adding parametres
            $valuesDatas = $this->generateSQLfromValuesInsert($values);
            $sql .= $valuesDatas['sql'];
        
            //Preparing insertion
            $insert = $this->db->prepare($sql);
            $result = $insert->execute($valuesDatas['params']);
            
            //Checking presence of errors
            if(!$result){
                $message = "An error occured while trying to add a line !";
                $message .= ($this->verbose ? "\n<i>SQL : ".$sql."</i>" : "");
                throw new Exception($message);
            }
            
            //Everything is OK
            return true;
        }
        catch(Exception $e){
            exit($this->echoException($e));
        }
        catch(PDOException $e){
            exit($this->echoPDOException($e));
        }
    }
    
    /**
     * Add more than one line to a table
     * 
     * @param String $tableName The name of the table
     * @param array $values The values of the lines
     */
    public function addLines($tableName, array $values){
        //We try to perform the task
        try{
            //We check if any database is opened
            if (!$this->checkOpenDB()) {
                throw new Exception("There isn't any opened DataBase !");
            }
            
            //Processing each line
            foreach($values as $process){
                if(is_array($process)){
                    if(!$this->addLine($tableName, $process)){
                        throw new Exception("An error occured while trying to "
                                . "add a line !");
                    }
                }
                else{
                    throw new Exception("A string has been given instead of an"
                            . " array !");
                }
            }
        }
        catch(Exception $e){
            exit($this->echoException($e));
        }
    }
    
    /**
     * Generates SQL code for editing DataBase for insert-like SQL commands
     * 
     * @param array $values The values for the SQL command
     * @return  Array   The SQL + the parametres
     */
    private function generateSQLfromValuesInsert(array $values){
        //Initialisating vars
        $sql = "(";
        $params = array();
        
        //Processing values
        foreach($values as $name => $value){
            //We add a coma if it is not the first value
            $sql .= ((count($params) != 0) ? ", " : "");
            
            //We add SQL for the name
            $sql .= $name;
            
            //Records the name parameters
            $params[] = $value;
        }
        
        //Continuing SQL
        $sql .= ") VALUES (";
        
        //Adding ? for each value
        for($i = 0; $i<count($params); $i++){
            $sql .= ($i != 0 ? ", " : "")."?";
        }
        
        //Finishing SQL
        $sql .= ") ";
        
        //Preparing return
        $return = array(
            "sql" => $sql,
            "params" => $params
        );
        
        //Returning values
        return $return;
    }
    
    /**
     * Get datas from a table
     * 
     * @param String $tableName The name of the table
     * @param String $conditions The conditions
     * @param Array $datasCond The values of condition
     * @return Array The result
     */
    public function select($tableName, $conditions = "", array $datasCond = array()){
        //We try to perform the task
        try{
            //We check if any database is opened
            if (!$this->checkOpenDB()) {
                throw new Exception("There isn't any opened DataBase !");
            }
        
            //Generating SQL
            $sql = "SELECT * FROM ".$tableName." ".$conditions;
            $selectOBJ = $this->db->prepare($sql);
            $selectOBJ->execute($datasCond);
            
            //Preparing return
            $return = array();
            foreach($selectOBJ as $process){
                $result = array();
                
                //Processing datas
                foreach($process as $name => $data){
                    //We save the data only if it is not an integer
                    if (!is_int($name)) {
                        $result[$name] = $data;
                    }
                }
                
                //Saving result
                $return[] = $result;
            }
            
            //Returning result
            return $return;
        }
        catch(Exception $e){
            exit($this->echoException($e));
        }
        catch(PDOException $e){
            exit($this->echoPDOException($e));
        }
    }

    /**
     * Count number of entries matching conditions
     *
     * @param String $tableName The name of the table
     * @param String $conditions The conditions
     * @param Array $datasCond The values of condition
     * @return Integer The result
     */
    public function count($tableName, $conditions = "", array $datasCond = array()){
        //We try to perform the task
        try{
            //We check if any database is opened
            if (!$this->checkOpenDB()) {
                throw new Exception("There isn't any opened DataBase !");
            }
        
            //Generating SQL
            $sql = "SELECT COUNT(*) AS resultNumber FROM ".$tableName." ".$conditions;
            $selectOBJ = $this->db->prepare($sql);
            $selectOBJ->execute($datasCond);
            
            //Preparing return
            foreach($selectOBJ as $process){
                $return = $process;
            }
            
            //Returning result
            return $return['resultNumber'];
        }
        catch(Exception $e){
            exit($this->echoException($e));
        }
        catch(PDOException $e){
            exit($this->echoPDOException($e));
        }
    }
    
    /**
     * Update a Table
     * 
     * @param String $tableName  The name of the table
     * @param String $conditions The conditions to limit the edition
     * @param Array $modifs      The modifications
     * @param Array $whereValues The values of the WHERE condition
     * @return Boolean Returns true if succeed.
     */
    public function updateDB($tableName, $conditions, array $modifs, array $whereValues){
        //We try to perform the task
        try{
            //We check if any database is opened
            if (!$this->checkOpenDB()) {
                throw new Exception("There isn't any opened DataBase !");
            }
            
            //Generating SQL for changes
            $modifValues = array();
            $sqlChange = "";
            foreach($modifs as $name=>$value){
                $sqlChange .= ($sqlChange != "" ? ", " : "").$name." = ?";
                
                //Saving data
                $modifValues[] = $value;
            }
            
            //Adding condition values to the liste of query
            $datasQuery = array_merge_recursive($modifValues, $whereValues);

            //Generating SQL
            $sql = "UPDATE ".$tableName." SET ".$sqlChange." WHERE ".$conditions;
            
            //Executing SQL
            $edit = $this->db->prepare($sql);
            
            //Trying to perform action
            if(!$edit->execute($datasQuery)) {
                $message = "Unable to perform UPDATE SQL ! <br />";
                $message .= ($this->verbose ? "\n<i>SQL : ".$sql."</i>" : "");
                throw new Exception($message);
            }
            
            //Returns true if succeed
            return true;
            
        }
        catch(Exception $e){
            exit($this->echoException($e));
        }
        catch(PDOException $e){
            exit($this->echoPDOException($e));
        }
    }
    
    /**
     * Delete entrie(s) from a table
     * 
     * @param String $tableName The name of the table
     * @param String $conditions The conditions to perform action
     * @param Array $conditionsValues The values of condition
     * @return Boolean  True if succeed
     */
    public function deleteEntry($tableName, $conditions = false, array $conditionsValues = array()) {
        //We try to perform the task
        try{
            //We check if any database is opened
            if (!$this->checkOpenDB()) {
                throw new Exception("There isn't any opened DataBase !");
            }
            
            //Generating SQL
            $sql = "DELETE FROM ".$tableName;
            $sql .= ($conditions ? " WHERE ".$conditions : "");
            
            //Preparing request
            $delete = $this->db->prepare($sql);
            
            //Trying to perform action
            if(!$delete->execute($conditionsValues)) {
                $message = "Unable to perform DELETE SQL ! <br />";
                $message .= ($this->verbose ? "\n<i>SQL : ".$sql."</i>" : "");
                throw new Exception($message);
            }
            
            //Returns true if succeed
            return true;
            
        }
        catch(Exception $e){
            exit($this->echoException($e));
        }
        catch(PDOException $e){
            exit($this->echoPDOException($e));
        }
    }
    
    /**
     * Echo an exception
     * 
     * @param Exception $e The Exception
     */
    private function echoException(Exception $e){
        $message = '<b>Exception in '.$e->getFile().' on line '.$e->getLine().' </b>: '.$e->getMessage();
        echo $message;
        
        //PDO informations
        if($this->verbose){
            echo "\n PDO last error:";
            print_r($this->db->errorInfo());
        }
    }
    
    /**
     * Echo a PDO exception
     * 
     * @param PDOException $e The PDOException
     */
    private function echoPDOException(PDOException $e){
        $message = '<b>Exception in '.$e->getFile().' on line '.$e->getLine().' </b>: '.$e->getMessage();
        echo $message;

        //PDO informations
        if($this->verbose){
            echo "\n PDO last error:";
            print_r($this->db->errorInfo);
        }
    }
}
