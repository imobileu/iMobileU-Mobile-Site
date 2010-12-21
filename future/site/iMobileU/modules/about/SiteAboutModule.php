<?php

class SiteAboutModule extends DrupalContentModule
{
   protected $id = 'about';
   protected $BASE_URL = 'http://imobileu.org';
   protected $PATH = 'feed';

   protected function initializeForPage()
   {
        switch ($this->page)
        {
            case 'index':
                if ($item = $this->fetchNode('3', true)) {
                    $this->assign('content', $item->getContent());
                    $this->assign('title', $item->getTitle());
                }
                break;
            default:
                $this->redirectTo('index');
        }
   }
        
}