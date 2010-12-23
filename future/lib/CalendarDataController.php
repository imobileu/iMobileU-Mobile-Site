<?php

class CalendarDataController extends DataController
{
    protected $DEFAULT_PARSER_CLASS='ICSDataParser';
    const DEFAULT_EVENT_CLASS='ICalEvent';
    const START_TIME_LIMIT=-2147483647; 
    const END_TIME_LIMIT=2147483647; 
    protected $startDate;
    protected $endDate;
    protected $calendar;
    protected $requiresDateFilter=true;
    protected $contentFilter;
    protected $supportsSearch = false;
    
    public function setRequiresDateFilter($bool)
    {
        $this->requiresDateFilter = $bool ? true : false;
    }

    public function addFilter($var, $value)
    {
        switch ($var)
        {
            case 'search': 
                if ($this->supportsSearch) {
                    return parent::addFilter($var, $value);
                } else {
                    $this->contentFilter = $value;
                }
                break;
            default:
                return parent::addFilter($var, $value);
        }
    }
    
    protected function cacheFolder()
    {
        return CACHE_DIR . "/Calendar";
    }
    
    protected function cacheFileSuffix()
    {
        return '.ics';
    }
    
    public function setStartDate(DateTime $time)
    {
        $this->startDate = $time;
    }
    
    public function startTimestamp()
    {
        return $this->startDate ? $this->startDate->format('U') : false;
    }

    public function setEndDate(DateTime $time)
    {
        $this->endDate = $time;
    }

    public function endTimestamp()
    {
        return $this->endDate ? $this->endDate->format('U') : false;
    }
    
    public function getEventCategories()
    {
        return $this->parser->getEventCategories();
    }
    
    public function setDuration($duration, $duration_units)
    {
        if (!$this->startDate) {
            return;
        } elseif (!preg_match("/^-?(\d+)$/", $duration)) {
            throw new Exception("Invalid duration $duration");
        }
        
        $this->endDate = clone($this->startDate);
        switch ($duration_units)
        {
            case 'year':
            case 'day':
            case 'month':
                $this->endDate->modify(sprintf("%s%s %s", $duration>=0 ? '+' : '', $duration, $duration_units));
                break;
            default:
                throw new Exception("Invalid duration unit $duration_units");
                break;
            
        }
    }
    
    public static function factory($args=null)
    {
        $args['CONTROLLER_CLASS'] = isset($args['CONTROLLER_CLASS']) ? $args['CONTROLLER_CLASS'] : __CLASS__;
        $args['EVENT_CLASS'] = isset($args['EVENT_CLASS']) ? $args['EVENT_CLASS'] : self::DEFAULT_EVENT_CLASS;
        $controller = parent::factory($args);
        
        return $controller;
    }

    public function getItem($id, $time=null)
    {
        //use the time to limit the range of events to seek (necessary for recurring events)
        if ($time) {
            $start = new DateTime(date('Y-m-d H:i:s', $time));
            $start->setTime(0,0,0);
            $end = clone $start;
            $end->setTime(23,59,59);
            $this->setStartDate($start);
            $this->setEndDate($end);
        }
        
        $items = $this->events();
        if (array_key_exists($id, $items)) {
            if (array_key_exists($time, $items[$id])) {
                return $items[$id][$time];
            }
        }
        
        return false;
    }
    
    protected function events($limit=null)
    {
        if (!$this->calendar) {
            $data = $this->getData();
            $this->calendar = $this->parseData($data);
        }

        $startTimestamp = $this->startTimestamp() ? $this->startTimestamp() : CalendarDataController::START_TIME_LIMIT;
        $endTimestamp = $this->endTimestamp() ? $this->endTimestamp() : CalendarDataController::END_TIME_LIMIT;
        $range = new TimeRange($startTimestamp, $endTimestamp);
        
        return $this->calendar->getEventsInRange($range, $limit);
    }
    
    protected function clearInternalCache()
    {
        $this->calendar = null;
        parent::clearInternalCache();
    }
    
    public function items($start=0, $limit=null) 
    {
        $items = $this->events($limit);
        $events = array();
        foreach ($items as $eventOccurrences) {
            foreach ($eventOccurrences as $occurrence) {
                if ($this->contentFilter) {
                    if ( (stripos($occurrence->get_description(), $this->contentFilter)!==FALSE) || (stripos($occurrence->get_summary(), $this->contentFilter)!==FALSE)) {
                        $events[] = $occurrence;
                    }
                } else {
                    $events[] = $occurrence;
                }
            }
        }

        return $this->limitItems($events, $start, $limit);
    }
}
