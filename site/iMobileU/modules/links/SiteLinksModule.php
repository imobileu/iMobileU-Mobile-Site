<?php

class SiteLinksModule extends LinksModule
{
  protected function getFeedForPage($page)
  {
    switch ($page)
    {
        case 'sites':
            return 'community-users.xml';
        case 'resources':
            return 'resources.xml';
    }
  }

  protected function initializeForPage() {
    switch ($this->page)
    {
        case 'sites':
        case 'resources':
            $DrupalDataController = DrupalDataController::factory($this->getSiteSection('drupal'));
            $items = $DrupalDataController->fetchFeed($this->getFeedForPage($this->page));
            $links = array();
            
            foreach ($items as $item) {
                if (preg_match('/<a href="([^"]+)">/', $item->getContent(), $bits)) {
                    $links[] = array(
                        'title'=>$item->getTitle(),
                        'url'=>$bits[1],
                        'class'=>'external'
                    );
                }
            }

            $this->assign('links', $links);
            break;
            
        case 'index':
            $this->assign('linksPages', array( 
                array(
                'title'=>'Framework Sites',
                'url'=>'sites'),
                array(
                'title'=>'Resources',
                'url'=>'resources'),
                ));
            break;
    }
  }
}
