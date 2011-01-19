<?php
/**
  * @package Module
  * @subpackage Stats
  */

/**
  */
require_once realpath(LIB_DIR.'/Module.php');

/**
  * @package Module
  * @subpackage Stats
  */
class StatsModule extends Module {
  protected $id = 'stats';
  protected $platforms = Array(
    'iphone' => 'iPhone',
    'android' => 'Android',
    'webos' => 'webOS',
    'winmo' => 'Windows Mobile',
    'blackberry' => 'BlackBerry',
    'bbplus' => 'Advanced BlackBerry',
    'symbian' => 'Symbian',
    'palmos' => 'Palm OS',
    'featurephone' => 'Other Phone',
    'computer' => 'Computer',
  );
  
  protected function nameForService($service)
  {
    $serviceTypes = Array('web' => 'Website', 'api' => 'Native App');
    return $serviceTypes[$service];
  }

  
  protected function getDurationForInterval($interval)
  {
    switch ($interval)
    {
        case 'day':
            return 7;
        case 'month':
        case 'quarter':
        case 'week':
            return 12;
    }
  }
  
  protected function initialize() {

  }

protected function compare_content($content1, $content2) {
  if($content1['count'] < $content2['count']) {
    return 1;
  }
  if($content1['count'] > $content2['count']) {
    return -1;
  }
  return 0;
}

protected function generate_popular_content($system, $data) {
  $viewcounts = array();
  if ($system == 'web') {
    $modules = array();
    $moduleData = $this->getAllModules();
    
    foreach ($moduleData as $moduleID => $module) {
      $modules[$moduleID] = $module->getModuleName();
    }
  } else { // api
    $modules = array(
      "people" => "Directory",
      "map" => "Map", 
      "calendar" => "Events",
      "courses" => "Courses", 
      "news" => "News",
      "dining" => "Dining",
      "shuttles" => "ShuttleTracker",
      );
  }

  foreach ($modules as $module => $title) {
    $viewcounts[$module] = 0;
  }

  foreach($data as $datum) {
    foreach ($datum as $field => $count) {
      if (array_key_exists($field, $viewcounts))
	$viewcounts[$field] += $count;
    }
  }

  $popular_pages = Array();
  foreach ($viewcounts as $module => $count) {
    $module_stats = array(
      'name' => $modules[$module],
      'count' => $count,
      );
    if ($system == 'web') {
      $module_stats['name'] = $moduleData[$module]->getModuleName();
      $module_stats['link'] = sprintf("%s%s/", URL_BASE, $module);
    }

    $popular_pages[] = $module_stats;
  }
  return $popular_pages;
}

protected function list_items($data, $title, $label) {
  usort($data, array($this,'compare_content'));
  $data = array_slice($data, 0, 10);
  return array("type"=>"list", "data"=>$data, "title"=>$title, "label"=>$label);
}

protected function platform_data($data) {

  // views by device
  $traffic = Array();
  foreach ($this->platforms as $platform => $title) {
    $traffic[$platform] = 0;
  }
  foreach($data as $datum) {
    foreach ($datum as $field => $count) {
      if (array_key_exists($field, $traffic))
	$traffic[$field] += $count;
    }
  }
  return $traffic;
}

protected function bar_percentage($data, $title) {
  $new_data = array();
  $total = array_sum(array_values($data));
  foreach($data as $key => $count) {
    $new_data[$key] = $this->per_cent($count, $total);
  }

  return array(
    "type" => "bar_percentage",
    "data" => $new_data,
    "title" => $title,
  );
}

protected function per_cent($part, $total) {
  return $total > 0 ? round(100 * $part / $total) : 0;
}

protected function format_intervals($data, $max_scale, $field, $interval_type) {
  $intervals = array();
  foreach($data as $datum) {
    $new_interval = Array();
    $new_interval['day'] = date('D', $datum['date']);
    if (($interval_type != 'day') && ($max_scale > 1000)) {
      $num_digits = min(2, max(0, 6 - strlen($datum[$field])));
     $new_interval['count'] = number_format($datum[$field]/1000, $num_digits);
     } else {
      $new_interval['count'] = $datum[$field];
    }
    $new_interval['percent'] = $this->per_cent($datum[$field], $max_scale);
    switch ($interval_type) {
    case 'day':
      $new_interval['date'] = date('n/j', $datum['date']);
      break;
    case 'week':
      $new_interval['date'] = date('n/j/Y', $datum['date']);
      break;
    case 'month':
      $new_interval['date'] = date('M', $datum['date']);
      break;
    case 'quarter':
      $new_interval['date'] = 'Q' . ((date('n', $datum['date']) + 2) / 3) . date("\ny", $datum['date']);
      break;
    }

    $intervals[] = $new_interval;
  }
  return $intervals;
}

 protected function summary_total($data, $field, $title) { 
  $total = 0;
  foreach($data as $datum) {
    if (is_array($datum)) {
      $total += $datum[$field];
    } else {
      $total += (int)$datum;
    }
  }
  return array("type"=>"total", "title"=>$title, "total"=>$total);
}

protected function trend($data, $field, $title, $interval_type) {
  $max_scale = $this->determine_scale($data, $field);
  if (($interval_type != 'day') && ($max_scale > 1000)) {
    $title = '1000s of ' . $title;
  }
  return array(
    "type" => "trend",
    "days" => $this->format_intervals($data, $max_scale, $field, $interval_type),
    "title" => $title,
  );
}

protected function determine_scale($values, $field) {
  // find the largest number of views in the days
  $max_views = 0;
  foreach($values as $datum) {
    if($datum[$field] > $max_views) {
      $max_views = $datum[$field];
    }
  }

  // determine the maximum to use for the bar graph
  $limits = array(1, 2, 4, 5);

  $found = False;
  $scale = 10;
  while(!$found) {
    foreach($limits as $limit) {
      if($limit * $scale > $max_views) {
        $max_scale = $limit * $scale;
        $found = True;
        break;
      }
    }
    $scale *= 10;
  }  
  return $max_scale;
}
  
  protected function initializeForPage() {
  
    switch ($this->page) {
      case 'index':

         $service = $this->getArg('service', 'web');       
         $interval = $this->getArg('interval', 'day');       
         $duration = $this->getDurationForInterval($interval);
         
         $statData = PageViews::view_past($service, $interval, $duration);
         
        if ($service=='web') {
          $statItems = array(
            $this->summary_total($statData, "total", "total page views"),
            $this->trend($statData, "total", 
                'Page Views by ' . ucfirst($interval), 
                $interval),
            $this->bar_percentage( $this->platform_data($statData), "Traffic by Platform"),
            $this->list_items($this->generate_popular_content('web', $statData), "Most Popular Content", "page views"),
            );
        } else { // api
          $statItems = array(
            //summary_total(PageViews::count_iphone_tokens(), "total", "active users"),
            $this->summary_total($statData, "total", "total API requests"),
            $this->trend($statData, "total", 
                'API Requests by ' . ucfirst($interval), 
                $interval),
            $this->list_items($this->generate_popular_content('api', $statData), "Most Popular Modules", "requests"),
            );
        }

        $serviceTypes = Array('web' => 'Website', 'api' => 'Native App');
        $interval_types = Array(
          'day' => Array('duration' => 7, 'title' => 'Week', 'numdays' => 7),
          'week' => Array('duration' => 12, 'title' => '12 Weeks', 'numdays' => 84),
          'month' => Array('duration' => 12, 'title' => 'Year', 'numdays' => 365),
          'quarter' => Array('duration' => 12, 'title' => '3 Years', 'numdays' => 1095),
          );
        
        $statclasses = Array();
        foreach ($interval_types as $type => $attrs) {
          $stclass = Array();
          $stclass['interval'] = $type;
          if ($interval == $type) {
            $stclass['active'] = ' class="active"';
          } else {
            $stclass['active'] = '';
          }
          $stclass['title'] = $attrs['title'];
          $statclasses[$type] = $stclass;
        }
        
         $this->assign('statsItems', $statItems);
         $this->assign('statsName', $this->nameForService($service)); //not really
         $this->assign('statsService', $service);
         $this->assign('statsInterval', $interval);
         $this->assign('statsDuration', $duration);
         $this->assign('statclasses', $statclasses);
         $this->assign('serviceTypes', $serviceTypes);
     
  }

}
}


