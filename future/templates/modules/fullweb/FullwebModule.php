<?php

require_once realpath(LIB_DIR.'/Module.php');

class FullwebModule extends Module {
  protected $id = 'fullweb';
  protected function getModuleDefaultData()
  {
    return array_merge(parent::getModuleDefaultData(), array(
        'url'=>''
        )
    );
  }
  
  
  protected function initializeForPage() {
     if ($url = $this->getModuleVar('url')) {
         header("Location: $url");
         die();
     } else {
        throw new Exception("URL not specified");
     }
  }
}