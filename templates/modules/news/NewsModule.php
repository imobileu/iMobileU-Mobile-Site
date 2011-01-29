<?php
/**
  * @package Module
  * @subpackage News
  */

/**
  * @package Module
  * @subpackage News
  */
class NewsModule extends Module {
  protected $id = 'news';
  protected $hasFeeds = true;
  protected $feeds = array();
  protected $feedFields = array('CACHE_LIFETIME'=>'Cache lifetime (seconds)','CONTROLLER_CLASS'=>'Controller Class','ITEM_CLASS'=>'Item Class', 'ENCLOSURE_CLASS'=>'Enclosure Class');
  protected $feedIndex=0;
  protected $feed;
  protected $maxPerPage=10;

  protected function getModuleDefaultData() {
    return array_merge(parent::getModuleDefaultData(), array(
        'NEWS_MAX_RESULTS'=>10
        )
    );
  }
  
  private function feedURLForFeed($feedIndex) {
    return isset($this->feeds[$feedIndex]) ? 
      $this->feeds[$feedIndex]['baseURL'] : null;
  }
  
  private function getImageForStory($story) {
    $image = $story->getImage();
    
    if ($image) {
      return array(
        'src'    => $image->getURL(),
        'width'  => $image->getProperty('width'),
        'height' => $image->getProperty('height'),
      );
    }
    
    return null;
  }

  protected function urlForPage($pageNumber) {
    $args = $this->args;
    $args['storyPage'] = $pageNumber;
    return $this->buildBreadcrumbURL('story', $args, false);
  }

  private function feedURL($feedIndex, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('index', array(
      'section' => $feedIndex
    ), $addBreadcrumb);
  }

  private function storyURL($story, $addBreadcrumb=true) {
    if ($storyID = $story->getGUID()) {
        return $this->buildBreadcrumbURL('story', array(
          'storyID'   => $storyID,
          'section'   => $this->feedIndex,
          'start'     => self::argVal($this->args, 'start'),
          'filter'    => self::argVal($this->args, 'filter')
        ), $addBreadcrumb);
    } elseif ($link = $story->getProperty('link')) {
        return $link;
    } else {
        return '';
    }
  }

  protected function prepareAdminForSection($section, &$adminModule) {
    switch ($section)
    {
        case 'feeds':
            $feeds = $this->loadFeedData();
            $adminModule->assign('feeds', $feeds);
            $adminModule->setTemplatePage('feedAdmin', $this->id);
            break;
        default:
            return parent::prepareAdminForSection($section, $adminModule);
    }
  }
  
  public function getFeeds() {
    return $this->feeds;
  }
  
  public function getFeed($index) {
    if (isset($this->feeds[$index])) {
        $feedData = $this->feeds[$index];
        $controller = RSSDataController::factory($feedData);
        $controller->setDebugMode($GLOBALS['siteConfig']->getVar('DATA_DEBUG'));
        return $controller;
    } else {
        throw new Exception("Error getting news feed for index $index");
    }
  }

  public function federatedSearch($searchTerms, $maxCount, &$results) {
    $start           = 0;
    $feedIndex       = 0; // currently it only searches the first feed. TO DO: search all feeds
    
    $this->feed->addFilter('search', $searchTerms);
    $items = $this->feed->items($start, $maxCount+1, $totalItems);
    
    $limit = min($maxCount, count($items));
    for ($i = 0; $i < $limit; $i++) {
      $results[] = array(
        'title' => $items[$i]->getTitle(),
        'url'   => $this->buildBreadcrumbURL("/{$this->id}/story", array(
          'storyID' => $items[$i]->getGUID(),
          'section' => $feedIndex,
          'start'   => $start,
          'filter'  => $searchTerms,
        ), false),
      );
    }
    
    return count($items);
  }
  
  protected function initialize() {
    $this->feeds      = $this->loadFeedData();
    if ($max = $this->getModuleVar('NEWS_MAX_RESULTS')) {
        $this->maxPerPage = $max;
    }
    
    $this->feedIndex = $this->getArg('section', 0);
    if (!isset($this->feeds[$this->feedIndex])) {
      $this->feedIndex = 0;
    }
    
    $this->feed = $this->getFeed($this->feedIndex);
    
  }

  protected function initializeForPage() {

    switch ($this->page) {
      case 'story':
        $searchTerms = $this->getArg('filter', false);
        if ($searchTerms) {
          $this->feed->addFilter('search', $searchTerms);
        }

        $storyID   = $this->getArg('storyID', false);
        $storyPage = $this->getArg('storyPage', '0');
        $story     = $this->feed->getItem($storyID);
        
        if (!$story) {
          throw new Exception("Story $storyID not found");
        }
        
        if (!$content = $story->getProperty('content')) {
          if ($url = $story->getProperty('link')) {
              header("Location: $url");
              exit();
          } else {
              throw new Exception("No content or link found for story $storyID");
          }
        }

        $body = $story->getDescription()."\n\n".$story->getLink();
        $shareEmailURL = $this->buildMailToLink("", $story->getTitle(), $body);

        $pubDate = strtotime($story->getProperty("pubDate"));
        $date = date("M d, Y", $pubDate);
        
        $this->enablePager($content, $this->feed->getEncoding(), $storyPage);
        
        $this->assign('date',          $date);
        $this->assign('storyURL',      urlencode($story->getLink()));
        $this->assign('shareEmailURL', $shareEmailURL);
        $this->assign('title',         $story->getTitle());
        $this->assign('shareRemark',   urlencode($story->getTitle()));
        $this->assign('author',        $story->getAuthor());
        $this->assign('image',         $this->getImageForStory($story));
        break;
        
      case 'search':
        $searchTerms = $this->getArg('filter');
        $start       = $this->getArg('start', 0);
        
        if ($searchTerms) {
          $this->setPageTitle('Search');

          $this->feed->addFilter('search', $searchTerms);
          $items = $this->feed->items($start, $this->maxPerPage, $totalItems);
          $stories = array();
          foreach ($items as $story) {
            $item = array(
              'title'       => $story->getTitle(),
              'description' => $story->getDescription(),
              'url'         => $this->storyURL($story),
              'image'       => $this->getImageForStory($story),
            );
            $stories[] = $item;
           }

          $previousURL = '';
          $nextURL = '';
          
          if ($totalItems > $this->maxPerPage) {
            $args = $this->args;
            if ($start > 0) {
              $args['start'] = $start - $this->maxPerPage;
              $previousURL = $this->buildBreadcrumbURL($this->page, $args, false);
            }
            
            if (($totalItems - $start) > $this->maxPerPage) {
              $args['start'] = $start + $this->maxPerPage;
              $nextURL = $this->buildBreadcrumbURL($this->page, $args, false);
            }
          }

          $extraArgs = array(
            'section' => $this->feedIndex
          );

          $this->assign('extraArgs',   $extraArgs);
          $this->assign('searchTerms', $searchTerms);
          $this->assign('stories',     $stories);
          $this->assign('previousURL', $previousURL);
          $this->assign('nextURL',     $nextURL);
          
        } else {
          $this->redirectTo('index'); // search was blank
        }
        break;
        
      case 'index':
        $start = $this->getArg('start', 0);
        $totalItems = 0;
      
        $items = $this->feed->items($start, $this->maxPerPage, $totalItems);
       
        $previousURL = null;
        $nextURL = null;
        if ($totalItems > $this->maxPerPage) {
          $args = $this->args;
          if ($start > 0) {
            $args['start'] = $start - $this->maxPerPage;
            $previousURL = $this->buildBreadcrumbURL($this->page, $args, false);
          }
          
          if (($totalItems - $start) > $this->maxPerPage) {
            $args['start'] = $start + $this->maxPerPage;
            $nextURL = $this->buildBreadcrumbURL($this->page, $args, false);
          }
        }
        
        $stories = array();
        foreach ($items as $story) {
          $item = array(
            'title'       => $story->getTitle(),
            'description' => $story->getDescription(),
            'url'         => $this->storyURL($story),
            'image'       => $this->getImageForStory($story),
          );
          $stories[] = $item;
        }
        
        $sections = array();
        foreach ($this->feeds as $index => $feedData) {
          $sections[] = array(
            'value'    => $index,
            'title'    => htmlentities($feedData['TITLE']),
            'selected' => ($this->feedIndex == $index),
            'url'      => $this->feedURL($index, false),
          );
        }
        
        $hiddenArgs = array(
          'section'=>$this->feedIndex
        );
        
        $this->assign('hiddenArgs',     $hiddenArgs);
        $this->assign('sections',       $sections);
        $this->assign('currentSection', $sections[$this->feedIndex]);
        $this->assign('stories',        $stories);
        $this->assign('isHome',         true);
        $this->assign('previousURL',    $previousURL);
        $this->assign('nextURL',        $nextURL);
        break;
    }
  }
}
