<?php
/**
 * Thrown when there is a PHP -> Sikuli connection error.
 */
class PHPSikuliException extends Exception
{

}//end class


/**
 * Thrown when there is a Sikuli error.
 */
class SikuliException extends Exception
{

}//end class


/**
 * Thrown when image or text find operation fails.
 */
class FindFailedException extends SikuliException
{

    public function __construct($message, $code=0, Exception $previous=null)
    {
        $match = array();
        preg_match('/FindFailed: can not find.*\(([^\)]+)\)/mi', $message, $match);
        $msg = 'Failed to find ';
        if (empty($match) === FALSE) {
            $msg .= $match[1];
        } else {
            preg_match('/FindFailed: can not find ([^\s]+)/mi', $message, $match);
            if (empty($match) === FALSE) {
                $msg .= $match[1];
            } else {
                $msg = $message;
            }
        }

        parent::__construct($msg, $code, $previous);

    }

}//end class