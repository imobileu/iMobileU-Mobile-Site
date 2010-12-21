<?php

abstract class DrupalContentModule extends Module
{
    protected $BASE_URL='';
    protected $PATH='';
    protected $NODE_PATH='node.xml';
    protected $TEASER_PATH='teaser.xml';
    protected $controller;
    
    protected function fetchNode($node, $replace_relative=false)
    {
        if (!$this->controller) {
            $this->controller = RSSDataController::factory();
        }

        $this->controller->setBaseURL(sprintf("%s/%s/%d/%s", $this->BASE_URL, $this->PATH, $node, $this->NODE_PATH));
        
        if (($items = $this->controller->items()) && isset($items[0])) {
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
                        $url = $this->BASE_URL . $url;
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
}

