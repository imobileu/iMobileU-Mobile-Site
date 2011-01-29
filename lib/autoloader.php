<?php
/**
 * Auto Loader
 * @package Core
 */

 /**
 * This function defines a autoloader that is run when a class needs to be instantiated but the corresponding
 * file has not been loaded. Files MUST be named with the same name as its class
 * currently it will search:
 * 1. If the className has Module in it, it will search the MODULES_DIR
 * 2. The SITE_LIB_DIR  (keep in mind that some files may manually include the LIB_DIR class
 * 3. The LIB_DIR 
 * 
 */
function siteLibAutoloader($className) {
    $paths = array();

    /* If the className has Module in it then use the modules dir */
    if (defined('MODULES_DIR') && preg_match("/(.*)Module/", $className, $bits)) {
        $paths[] = MODULES_DIR . '/' . strtolower($bits[1]);
    }

    /* use the site lib dir if it's been defined */
    if (defined('SITE_LIB_DIR')) {
        $paths[] = SITE_LIB_DIR;
    }

    $paths[] = LIB_DIR;
        
    foreach ($paths as $path) {
        $file = realpath_exists("$path/$className.php");
        if ($file) {
            require_once $file;
            return;
        }
    }
    return;
}
