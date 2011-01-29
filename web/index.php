<?php
/**
  * @package Core
  */

/**
  */
require_once realpath(dirname(__FILE__).'/../lib/initialize.php');

/**
  */
function _404()
{
    header("HTTP/1.0 404 Not Found");
    echo "<h1>404 Not Found</h1>\n";
    echo "The page that you have requested could not be found.\n";
    exit();
}

//
// Configure web application
// modifies $path for us to strip prefix and device
//

$path = isset($_GET['_path']) ? $_GET['_path'] : '';

Initialize($path); 

//
// Handle page request
//

if (preg_match(';^.*favicon.ico$;', $path, $matches)) {
  $icon = realpath_exists(THEME_DIR.'/common/images/favicon.ico');
  if ($icon) {
    CacheHeaders($icon);
    header('Content-type: '.mime_type($icon));
    echo file_get_contents($icon);
    exit;
  }

  _404();
} else if (preg_match(';^.*ga.php$;', $path, $matches)) {
  //
  // Google Analytics for non-Javascript devices
  //
  
  require_once realpath(LIB_DIR.'/ga.php');
  exit;

} else if (preg_match(';^.*(modules|common)(/.*images)/(.*)$;', $path, $matches)) {
  //
  // Images
  //
  
  $file = $matches[3];

  $platform = $GLOBALS['deviceClassifier']->getPlatform();
  $pagetype = $GLOBALS['deviceClassifier']->getPagetype();
  
  $testDirs = array(
    THEME_DIR.'/'.$matches[1].$matches[2],
    TEMPLATES_DIR.'/'.$matches[1].$matches[2],
  );
  $testFiles = array(
    "$pagetype-$platform/$file",
    "$pagetype/$file",
    "$file",
  );
  
  foreach ($testDirs as $dir) {
    foreach ($testFiles as $file) {
      $image = realpath_exists("$dir/$file");
      if ($image) {
        CacheHeaders($image);
        header('Content-type: '.mime_type($image));
        echo file_get_contents($image);
        exit;
      }        
    }
  }
  
    //not found
  _404();

} else if (preg_match(';^.*(modules|common)(/.*(javascript|css))/(.*)$;', $path, $matches)) {
  $file = $matches[4];

  $platform = $GLOBALS['deviceClassifier']->getPlatform();
  $pagetype = $GLOBALS['deviceClassifier']->getPagetype();
  
  $testDirs = array(
    SITE_DIR.'/'.$matches[1].$matches[2],
    TEMPLATES_DIR.'/'.$matches[1].$matches[2]
  );

  $testFiles = array(
    "$pagetype-$platform/$file",
    "$pagetype/$file",
    "$file",
  );
  
  foreach ($testDirs as $dir) {
    foreach ($testFiles as $file) {
      $file = realpath_exists("$dir/$file");
      if ($file) {
      
        CacheHeaders($file);
        header("Content-type: text/" . $matches[3]);
        readfile($file);
        exit;
      }        
    }
  }

    //not found  
  _404();

} else if (preg_match(';^.*media/(.*)$;', $path, $matches)) {
  //
  // Media
  //

  $media = realpath_exists(SITE_DIR."/media/$matches[1]");
  if ($media) {
    header('Content-type: '.mime_type($media));
    echo file_get_contents($media);
    exit;
  }

    //not found  
  _404();
  
} else if (preg_match(';^.*(sample/.*)$;', $path, $matches)) {
  //
  // Sample Files
  //

  $sample = realpath_exists(SITE_DIR."/$matches[1].php");
  if ($sample) {
    require_once $sample;
    exit;
  }

    //not found  
  _404();
    
} else {
  //
  // Web Interface
  //
  
  require_once realpath(LIB_DIR.'/Module.php');
  require_once realpath(LIB_DIR.'/PageViews.php');
    
  $id = 'home';
  $page = 'index';
  
  $args = array_merge($_GET, $_POST);
  unset($args['_path']);
  if (get_magic_quotes_gpc()) {
    
    function deepStripSlashes($v) {
      return is_array($v) ? array_map('deepStripSlashes', $v) : stripslashes($v);
    }
    $args = deepStripslashes($args);
  }
  
  if (!strlen($path) || $path == '/') {
    if ($GLOBALS['deviceClassifier']->isComputer() || $GLOBALS['deviceClassifier']->isSpider()) {
      header("Location: ./info/");
    } else {
      header("Location: ./home/");
    }
  } else {  
    $parts = explode('/', ltrim($path, '/'), 2);

    $id = $parts[0];
    if (isset($parts[1])) {
      if (strlen($parts[1])) {
        $page = basename($parts[1], '.php');
      }
    } else {
      // redirect with trailing slash for completeness
      header("Location: ./$id/");
    }
  }

  PageViews::increment($id, $GLOBALS['deviceClassifier']->getPlatform());

  $module = Module::factory($id, $page, $args);
  $module->displayPage();
  exit;
}

//
// Unsupported Request
//

_404();