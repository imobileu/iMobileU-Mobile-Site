<?php
/**
  * @package Core
  */

/**
  * @package Core
  */
class Validator
{
    public static function isValidEmail($value) 
    {
        if (!is_string($value)) {
            return false;
        }
        $pattern = "/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/";
        return preg_match($pattern, $value);
    }

    /* this is currently only valid for US phone numbers */
    public static function isValidPhone($value, &$bits=null)
    {
        if (!is_scalar($value)) {
            return false;
        }
        $pattern = '/^\(?(\d\d\d)?[-).\s]*(\d\d\d)[-.\s]?(\d\d\d\d)$/';
        return preg_match($pattern, $value, $bits);
    }

    public static function isValidURL($value)
    {
        if (!is_scalar($value)) {
            return false;
        }

        $pattern = "@^((https?://)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)$@"; // From http://snipplr.com/view/36992/improvement-of-url-interpretation-with-regex/
        return preg_match($pattern, $value);
    }
}

