<?php
/**
 * Error functions
 *
 * @author Pierre HUBERT
 */

/**
 * Display a rest fatal error
 *
 * @param Integer $errorCode The code of the error
 * @param String $errorMessage The message of the error
 */
function Rest_fatal_error($errorCode, $errorMessage){
    //Returns a fatal error code
    http_response_code($errorCode);

    //Display message
    header("Content-Type: application/json");

    echo json_encode(array(
        "error" => array(
            "code"=>$errorCode,
            "message" => $errorMessage,
        )
    ), JSON_PRETTY_PRINT);

    //Quit
    exit();
}