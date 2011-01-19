<?php

require_once realpath(LIB_DIR.'/Module.php');

class SiteContactModule extends Module {
   protected $id = 'contact';
  
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
        $DrupalDataController = DrupalDataController::factory($this->getSiteSection('drupal'));

        if ($item = $DrupalDataController->fetchNode($this->getNodeForPage($this->page), true)) {
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
