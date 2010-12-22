<?php

class DrupalDataController extends RSSDataController
{
    protected $DRUPAL_SITE_BASE_URL;
    protected $DRUPAL_SITE_FEED_PATH;
    protected $DRUPAL_SITE_NODE_PATH;
    protected $DRUPAL_SITE_TEASER_PATH;
    
    protected function init($args)
    {
        parent::init($args);
        foreach (array('DRUPAL_SITE_BASE_URL',
                        'DRUPAL_SITE_FEED_PATH', 
                        'DRUPAL_SITE_NODE_PATH', 
                        'DRUPAL_SITE_TEASER_PATH') as $arg) {
            if (isset($args[$arg])) {
                $this->$arg = $args[$arg];
            }
        }
    }

    public static function factory($args=null)
    {
        $args['CONTROLLER_CLASS'] = isset($args['CONTROLLER_CLASS']) ? $args['CONTROLLER_CLASS'] : __CLASS__;
        $controller = parent::factory($args);
        
        return $controller;
    }

    protected function fetchContent($replace_relative=false)
    {
        if (($items = $this->items()) && isset($items[0])) {
            $item = $items[0];
            if ($replace_relative) {
                $content = new RSSElement('content');
                $bits = preg_split('/(src|href)\s*=\s*"([^"]+)"/', $item->getContent(), -1, PREG_SPLIT_DELIM_CAPTURE );
                $i = 0;
                $content->setValue(array_shift($bits));
        
                while ( $i<count( $bits ) ) {
                    $attrib = $bits[$i++];
                    $url = $bits[$i++];
                    if ($url[0]=='/') {
                        $url = $this->DRUPAL_SITE_BASE_URL . $url;
                    } elseif (!preg_match("/^(http|mailto)/", $url)) {
                        trigger_error("Relative URL $url found, this might not be handled properly", E_USER_WARNING);
                    }
                    $trail = $bits[$i++];
                    $content->appendValue(sprintf('%s="%s"%s', $attrib, $url, $trail));
                }
                $item->addElement($content);
            }
            return $item;
        } else {
            return false;
        }
    }

    public function fetchTeaser($node, $replace_relative=false)
    {
        $this->setBaseURL(sprintf("%s/%s/%d/%s", $this->DRUPAL_SITE_BASE_URL, $this->DRUPAL_SITE_FEED_PATH, $node, $this->DRUPAL_SITE_TEASER_PATH));
        return $this->fetchContent($replace_relative);
    }
    
    public function fetchNode($node, $replace_relative=false)
    {
        $this->setBaseURL(sprintf("%s/%s/%d/%s", $this->DRUPAL_SITE_BASE_URL, $this->DRUPAL_SITE_FEED_PATH, $node, $this->DRUPAL_SITE_NODE_PATH));
        return $this->fetchContent($replace_relative);
    }
}

