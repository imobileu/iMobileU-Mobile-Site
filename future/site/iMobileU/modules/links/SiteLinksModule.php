<?php

class SiteLinksModule extends LinksModule
{
  protected function initializeForPage() {
    switch ($this->page)
    {
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
