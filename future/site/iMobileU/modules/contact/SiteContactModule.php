<?php

require_once realpath(LIB_DIR.'/Module.php');

class SiteContactModule extends DrupalContentModule {
   protected $id = 'contact';
   protected $BASE_URL = 'http://imobileu.org';
   protected $PATH = 'feed';
  
  protected function getNodeForPage($page)
  {
    switch ($page)
    {
        case 'mailinglist':
            return 16;
        case 'feedback':
            return 23;
    }
  }

  protected function initializeForPage() {
    switch ($this->page)
    {
      case 'mailinglist':
      case 'feedback':
        if ($item = $this->fetchNode($this->getNodeForPage($this->page), true)) {
            $this->assign('content', $item->getContent());
            $this->assign('title', $item->getTitle());
            $this->setTemplatePage('content');
        } else {
            throw new Exception("Unable to retrieve content for node $this->page");
        }
        break;

    case 'index':
        $this->loadWebAppConfigFile('contact-index', 'contactPages');
        break;
   }
}
  
  
}
