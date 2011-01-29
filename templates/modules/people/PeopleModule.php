<?php
/**
  * @package Module
  * @subpackage People
  */

/**
  * @package Module
  * @subpackage People
  */
class PeopleModule extends Module {
  protected $id = 'people';
  
  private $detailFields = array();
  private $detailAttributes = array();
  protected $feeds=array();
  protected $feedFields = array('CONTROLLER_CLASS'=>'Controller Class','PERSON_CLASS'=>'Person Class');
  protected $hasFeeds = true;
  
  private function formatValues($values, $info) {
    if (isset($info['parse'])) {
      $formatFunction = create_function('$value', $info['parse']);
      foreach ($values as &$value) {
        $value = $formatFunction($value);
      }
    }
    return $values;
  }
  
  private function formatDetail($values, $info) {
    if (isset($info['format'])) {
      $value = vsprintf($info['format'], $values);
    } else {
      $value = implode(' ', $values);
    }
    
    $detail = array(
      'label' => $info['label'],
      'title' => $value,
    );
    
    switch(isset($info['type']) ? $info['type'] : 'text') {
      case 'email':
        $detail['title'] = str_replace('@', '@&shy;', $detail['title']);
        
        $detail['url'] = "mailto:$value";
        $detail['class'] = 'email';
        break;
        
      case 'phone':
        $detail['title'] = str_replace('-', '-&shy;', $detail['title']);
        
        if (strpos($value, '+1') !== 0) { $value = "+1$value"; }
        $detail['url'] = 'tel:'.strtr($value, '-', '');
        $detail['class'] = 'phone';
        break;
        
      case 'map':
        // Only send the next-to-last line of the address to the map module
        $lines = explode('$', $value);
        $count = count($lines);
        $linkAddress = ($count > 1) ? $lines[$count - 2] : $value;
        $detail['url'] = self::buildURLForModule('map', 'search', array(
          'filter' => $linkAddress
        ));
        $detail['class'] = 'map';
        break;
    }
    
    $detail['title'] = str_replace('$', '<br />', $detail['title']); // $ is the LDAP multiline char
    
    return $detail;
  }
  
  private function formatPersonDetail($person, $info) {
    $section = array();
    
    if (count($info['attributes']) == 1) {
      $values = (array)$person->getField($info['attributes'][0]);
      if (count($values)) {
        $section[] = $this->formatDetail($this->formatValues($values, $info), $info);
      }      
    } else {
      $valueGroups = array();
    
      foreach ($info['attributes'] as $attribute) {
        $values = $this->formatValues((array)$person->getField($attribute), $info);
        
        if (count($values)) {
          foreach ($values as $i => $value) {
            $valueGroups[$i][] = $value;
          }
        }
      }
      foreach ($valueGroups as $valueGroup) {
        $section[] = $this->formatDetail($valueGroup, $info);
      }
    }
    
    return $section;
  }
  
  private function formatPersonDetails($person) {
    //error_log(print_r($this->detailFields, true));
    
    $details = array();    
    foreach($this->detailFields as $key => $info) {
      $section = $this->formatPersonDetail($person, $info);
      
      if (count($section)) {
        if (isset($info['section'])) {
          if (!isset($details[$info['section']])) {
            $details[$info['section']] = $section;
          } else {
            $details[$info['section']] = array_merge($details[$info['section']], $section);
          }
        } else {
          $details[] = $section;
        }
      }
    }
    //error_log(print_r($details, true));
    return $details;
  }
  
  public function federatedSearch($searchTerms, $maxCount, &$results) {
    $total = 0;
    $results = array();
  
    $PeopleController = $this->getFeed('people');
    
    $people = $PeopleController->search($searchTerms);

    if ($people !== false) {
      $limit = min($maxCount, count($people));
      for ($i = 0; $i < $limit; $i++) {
        $section = $this->formatPersonDetail($people[$i], $this->detailFields['name']);
        
        $results[] = array(
          'url' => $this->buildBreadcrumbURL("/{$this->id}/detail", array(
             'uid'    => $people[$i]->getId(),
             'filter' => $searchTerms
          ), false),
          'title' => htmlentities($section[0]['title']),
        );
      }
    }
    return count($people);
  }

  protected function prepareAdminForSection($section, &$adminModule) {
    switch ($section)
    {
        case 'feeds':
            $feeds = $this->loadFeedData();
            $adminModule->assign('feeds', $feeds);
            $adminModule->setTemplatePage('feedAdmin', $this->id);
            $formListItems = array();
            foreach ($feeds as $feed=>$data) {
                foreach ($data as $key=>$value) {
                    $formListItems[] = array(
                        'label'=>$key,
                        'type'=>'text',
                        'name'=>sprintf("moduleData[feeds][%s][%s]", $feed, $key),
                        'value'=>$value
                    );
                }
            }
            
            $adminModule->assign('peopleAdminListItems', $formListItems);
            break;
        default:
            return parent::prepareAdminForSection($section, $adminModule);
        
    }
  }
  
  protected function getFeed($index)
  {
    if (isset($this->feeds[$index])) {
        $feedData = $this->feeds[$index];
        $controller = PeopleController::factory($feedData);
        $controller->setAttributes($this->detailAttributes);
        $controller->setDebugMode($this->getSiteVar('DATA_DEBUG'));
        return $controller;
    } else {
        throw new Exception("Error getting people feed for index $index");
    }
  }
  
  protected function initialize() {
    $this->feeds = $this->loadFeedData();
    $this->detailFields = $this->loadWebAppConfigFile('people-detail', 'detailFields');
    foreach($this->detailFields as $field => $info) {
      $this->detailAttributes = array_merge($this->detailAttributes, $info['attributes']);
    }
    $this->detailAttributes = array_unique($this->detailAttributes);
  }

  protected function initializeForPage() {
    $PeopleController = $this->getFeed('people');
    
    if ($this->getSiteVar('MODULE_DEBUG')) {
      $this->addModuleDebugString($PeopleController->debugInfo());
    }
    
    switch ($this->page) {
      case 'help':
        break;
        
      case 'detail':
        if ($uid = $this->getArg('uid')) {
          $person = $PeopleController->lookupUser($uid);
          
          if ($person) {
            $this->assign('personDetails', $this->formatPersonDetails($person));
          } else {
            $this->assign('searchError', $PeopleController->getError());
          }          
        } else {
          $this->assign('searchError', 'No username specified');
        }
        break;
        
      case 'search':
        if ($filter = $this->getArg('filter')) {
          $searchTerms = trim($filter);
          
          $this->assign('searchTerms', $searchTerms);
          
          $people = $PeopleController->search($searchTerms);
          $this->assign('searchError', $PeopleController->getError());

          if ($people !== false) {
            $resultCount = count($people);
            
            switch ($resultCount) {
              case 0:
                break;
              
              case 1:
                $person = $people[0];
                $this->redirectTo('detail', array(
                    'uid'=>$person->getId()
                    )
                );
                break;
                
              default:
                $results = array();
                
                foreach ($people as $person) {
                  $section = $this->formatPersonDetail($person, $this->detailFields['name']);
                  
                  $results[] = array(
                    'url' => $this->buildBreadcrumbURL('detail', array(
                       'uid'    => $person->getId(),
                       'filter' => $this->getArg('filter')
                    )),
                    'title' => htmlentities($section[0]['title']),
                  );
                }
                //error_log(print_r($results, true));
                $this->assign('resultCount', $resultCount);
                $this->assign('results', $results);
                break;
            }
          
          } else {
            $this->assign('searchError', $PeopleController->getError());
          }
        } else {
          $this->redirectTo('index');
        }
        break;
        
      case 'index':
        // Redirect for old bookmarks
        if ($this->getArg('uid')) {
          $this->redirectTo('detail');
    
        } else if ($this->getArg('filter')) {
          $this->redirectTo('search');
        }
        
        $this->loadWebAppConfigFile('people-index', 'contacts');
        break;
    }  
  }
}
