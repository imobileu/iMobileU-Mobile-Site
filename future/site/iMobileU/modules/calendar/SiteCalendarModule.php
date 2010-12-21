<?php

class SiteCalendarModule extends CalendarModule {

  protected function initializeForPage() {
    switch ($this->page) {
      case 'list':
        parent::initializeForPage();
        break;
       default:
        $this->redirectTo('list');
        break;
      }
  }
}
