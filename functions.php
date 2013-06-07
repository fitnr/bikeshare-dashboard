<?php
/**
 * @package WordPress
 * @subpackage bikeshare-dashboard
 */

// don't allow archive pages
add_filter( 'archive_template', 'noop' );

function noop() {}

add_action('template_redirect', 'dashboard_request');

function dashboard_request() {
  if (preg_match(',/system_activity/,', $_SERVER['REQUEST_URI'])) {
    header("HTTP/1.0 200 OK");
    $args = get_overview_data();
    echo abstract_bikeshare_dashboard($args);
    exit;
    
  } elseif (preg_match(',/system_activity_csv/,', $_SERVER['REQUEST_URI'])) {
    $args = get_overview_data();
    $args['output'] = 'csv';
    $output = abstract_bikeshare_dashboard($args);
    header("HTTP/1.0 200 OK");
    header('Content-type: application/CSV');
    header('Content-Disposition: attachment; filename="'. $output['filename'] .'.csv"');
    header("Pragma: no-cache");
    header("Expires: 0");
    echo $output['csv'];
    exit;

  } elseif (preg_match('-/station_activity/(\d{1,4})/-', $_SERVER['REQUEST_URI'], $matches)) {
    header("HTTP/1.0 200 OK");
    echo station_activity($matches[1]);
    exit;

  } elseif (preg_match('-/station_activity_csv/(\d{1,4})/-', $_SERVER['REQUEST_URI'], $matches)) {
    $output = station_activity($matches[1], 'csv');
    header("HTTP/1.0 200 OK");
    header('Content-type: application/CSV');
    header('Content-Disposition: attachment; filename="'. $output['filename'] .'.csv"');
    header("Pragma: no-cache");
    header("Expires: 0");
    echo $output['csv'];
    exit;
  }
}

// add since to the query vars
add_filter('query_vars', 'since_queryvars');

function since_queryvars($qvars) {
  $qvars[] = 'since';
  return $qvars;
}

add_filter('query_vars', 'station_queryvars');

function station_queryvars($qvars) {
  $qvars[] = 'station';
  return $qvars;
}

function abstract_bikeshare_dashboard($kwargs) {
  global $wpdb;

  $data = $wpdb->get_results(sprintf($kwargs['q'], $kwargs['since'], $kwargs['filter']));
  if ($kwargs['output']=='csv'):
    return output_csv($kwargs['fileName'], $data);
  else:
    return json_encode($data);
  endif;
}

function get_station_name($id) { // Internal function, not in API
  global $wpdb;
   $q = "SELECT stationName from stations where id=$id";
   $data = $wpdb->get_results($q);
   return $data[0]->stationName;
}

// Get overview information for the whole system
function get_overview_data($since=6) {
  // $since is in hours
  global $wp_query;
  
  if (isset($wp_query->query_vars['since'])):
     $since = $wp_query->query_vars['since'];
  endif;

  $q = "SELECT s.stamp datetime, s.totalDocks Total_Docks, s.availDocks Available_Docks, s.availBikes Available_Bikes, s.nullDocks Null_Docks, s.fullStations Full_Stations, s.plannedStations Planned_Stations, s.emptyStations Empty_Stations, s.inactiveStations Inactive_Stations FROM status_report s WHERE (s.stamp > NOW() - INTERVAL %d HOUR) ORDER BY s.stamp ASC;";
  return array('q'=>$q, 'since'=>$since, 'fileName'=>'overview-' . date('Y-m-d'));
}

// Get information about the status of each station in the system
function station_overview($since=1) {
  // $since is in hours
  global $wp_query;
  if(isset($wp_query->query_vars['since'])):
     $since = $wp_query->query_vars['since'];
  endif;

  $q = "SELECT MIN(x.availableDocks) minDocks, MAX(x.availableDocks) maxDocks, MAX(x.availableDocks)-MIN(x.availableDocks) diffDocks, MAX(x.statusKey) status, x.station_id id, y.stationName FROM station_status x JOIN stations y ON x.station_id = y.id WHERE (x.stamp > NOW() - INTERVAL %d HOUR) GROUP BY x.station_id";
  return array('q'=>$q, 'since'=>$since);
}

// The status of a particular station over time.
function station_activity($station_id, $output='json', $since=6) {
  // since is in hours
  global $wpdb;
  global $wp_query;
  if (isset($wp_query->query_vars['since'])):
     $since = $wp_query->query_vars['since'];
  endif;

  $filter = '';
  if ($since>24) {
    $filter = "AND MINUTE(`stamp`) % 2 = 0"; // Get records from only even minutes
  }
  if ($since>72) {
    $filter = "AND MINUTE(`stamp`) % 3 = 0"; // Get records from every third minute
  }

  $j = "SELECT `id`, `stationName` FROM stations y WHERE `id`=%d";

  $q = "SELECT stamp datetime, availableDocks Available_Docks, availableBikes Available_Bikes, (totalDocks - availableDocks - availableBikes) Null_Docks FROM station_status WHERE station_id=%d and (stamp > NOW() - INTERVAL %d HOUR) %s ORDER BY stamp ASC;";

  $data = $wpdb->get_results(sprintf($q, $station_id, $since, $filter));
  $hed = $wpdb->get_results(sprintf($j, $station_id));

  if ($output=='csv'):
    return output_csv($hed[0]->stationName, $data);
  else:
    return json_encode(array("stationInfo"=>$hed[0], "activity"=>$data, 'query'=>sprintf($q, $station_id, $since, $filter)));
  endif;
}

function output_csv($filename, $data) {
  // For filename: strange characters to underscore, remove spaces
  $filename = preg_replace(array('/[^a-z0-9_\-]/i', '/ /'), array('_', ''), $filename);
  $fieldnames = array_keys(get_object_vars($data[1]));

  $format = str_repeat('%s,', count($fieldnames));
  $format = substr($format, 0, -1) . "\n"; // remove trailing comma

  // Start the csv w/ header line
  $csv = vsprintf($format, $fieldnames);

  foreach($data as $row)
    $csv .= vsprintf($format, (array) $row);

  return array('filename'=>$filename, 'csv'=>$csv);
}

function pluralize($x, $singular='hour', $plural='hours') {
    return ($x == 1) ? $singular : $x . ' '.$plural;
}
?>