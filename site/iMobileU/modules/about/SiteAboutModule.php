<?php

class SiteAboutModule extends Module
{
   protected $id = 'about';
   protected $BASE_URL = 'http://imobileu.org';
   protected $PATH = 'feed';

   protected function initializeForPage()
   {
        switch ($this->page)
        {
            case 'index':
                $DrupalDataController = DrupalDataController::factory($this->getSiteSection('drupal'));
        
                if ($item = $DrupalDataController->fetchNode('3', true)) {
                    $this->assign('content', $item->getContent());
                    $this->assign('title', $item->getTitle());
                }
                break;
            default:
                $this->redirectTo('index');
        }
   }
        
}