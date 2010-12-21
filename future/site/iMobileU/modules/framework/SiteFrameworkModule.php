<?php

require_once realpath(LIB_DIR.'/Module.php');

class SiteFrameworkModule extends DrupalContentModule {
   protected $id = 'framework';
   protected $BASE_URL = 'http://imobileu.org';
   protected $PATH = 'feed';
  
  protected function getNodeForPage($page)
  {
    switch ($page)
    {
        case 'overview':
            return 14;
        case 'modules':
            return 22;
    }
  }

  protected function initializeForPage() {
    switch ($this->page)
    {
      case 'overview':
      case 'modules':
        if ($item = $this->fetchNode($this->getNodeForPage($this->page), true)) {
            $this->assign('content', $item->getContent());
            $this->assign('title', $item->getTitle());
            $this->setTemplatePage('content');
        } else {
            throw new Exception("Unable to retrieve content for node $this->page");
        }
        break;
      case 'index':
        $this->loadWebAppConfigFile('framework-index', 'frameworkPages');
        break;
                    
    }
  }
  
  
}
