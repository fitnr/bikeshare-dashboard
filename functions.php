<?php
/**
 * @package WordPress
 * @subpackage bikeshare-dashboard
 */

// don't allow archive pages
add_filter( 'archive_template', 'noop' );

function noop() {}

/*
 * Remove feeds and such from the head.
*/
remove_action( 'wp_head', 'feed_links_extra', 3 ); // Display the links to the extra feeds such as category feeds
remove_action( 'wp_head', 'feed_links', 2 ); // Display the links to the general feeds: Post and Comment Feed
remove_action( 'wp_head', 'rsd_link' ); // Display the link to the Really Simple Discovery service endpoint, EditURI link
remove_action( 'wp_head', 'wlwmanifest_link' ); // Display the link to the Windows Live Writer manifest file.
// remove_action( 'wp_head', 'index_rel_link' ); // index link
remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 ); // prev link
remove_action( 'wp_head', 'start_post_rel_link', 10, 0 ); // start link
remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 ); // Display relational links for the posts adjacent to the current post.
remove_action( 'wp_head', 'wp_generator' ); // Display the XHTML generator that is generated on the wp_head hook, WP version
remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );


/*
 * Add api functions for wordpress to respond to.
*/
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

  } elseif (preg_match('-/station_activity/(\d+)/-', $_SERVER['REQUEST_URI'], $matches)) {
    header("HTTP/1.0 200 OK");
    echo station_activity($matches[1]);
    exit;

  } elseif (preg_match('-/station_activity_csv/(\d+)/-', $_SERVER['REQUEST_URI'], $matches)) {
    $output = station_activity($matches[1], 'csv');
    header("HTTP/1.0 200 OK");
    header('Content-type: application/CSV');
    header('Content-Disposition: attachment; filename="'. $output['filename'] .'.csv"');
    header("Pragma: no-cache");
    header("Expires: 0");
    echo $output['csv'];
    exit;
  } else if (preg_match(',/get_station_locations$,', $_SERVER['REQUEST_URI'])) {
    header("HTTP/1.0 200 OK");
    echo station_locations();
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

  $data = $wpdb->get_results($wpdb->prepare($kwargs['q'], $kwargs['since'], $kwargs['filter']));
  if ($kwargs['output']=='csv'):
    return output_csv($kwargs['fileName'], $data);
  else:
    return json_encode($data);
  endif;
}

function get_station_meta($id) { // Internal function, not in API
  global $wpdb;
  $data = $wpdb->get_results($wpdb->prepare("SELECT s.stationName, r.totalDocks from stations s JOIN station_status r ON s.llid=r.llid where s.llid=%d LIMIT 1", $id));
  return $data[0];
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

  $q = "SELECT MIN(x.availableDocks) minDocks, MAX(x.availableDocks) maxDocks, MAX(x.availableDocks)-MIN(x.availableDocks) diffDocks, MAX(x.statusKey) status, x.llid id, y.stationName FROM station_status x JOIN stations y ON x.llid = y.llid WHERE (x.stamp > NOW() - INTERVAL %d HOUR) GROUP BY x.llid";
  return array('q'=>$q, 'since'=>$since);
}

// The status of a particular station over time.
function station_activity($llid, $output='json', $since=6) {
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

  $j = "SELECT `stationName` FROM stations y WHERE `llid`=%d";

  $q = "SELECT stamp datetime, availableDocks Available_Docks, availableBikes Available_Bikes, (totalDocks - availableDocks - availableBikes) Null_Docks FROM station_status WHERE llid=%d and (stamp > NOW() - INTERVAL %d HOUR) ". $filter ." ORDER BY stamp ASC;";

  $data = $wpdb->get_results($wpdb->prepare($q, $llid, $since, $filter));
  $stationName = $wpdb->get_var($wpdb->prepare($j, $llid));

  if ($output=='csv'):
    return output_csv($stationName, $data);
  else:
    return json_encode(array("stationInfo"=>$stationName, "activity"=>$data));
  endif;
}

function station_locations(){
  global $wpdb;
  $data = $wpdb->get_results("SELECT x.llid, x.stationName, x.latitude lat, x.longitude lon, y.availableDocks, y.availableBikes, y.totalDocks, IF(statusKey=1 AND availableDocks=0,1,0) fullFlag, IF(statusKey=1 AND availableBikes=0,1,0) emptyFlag, statusValue FROM stations x INNER JOIN station_status y ON (x.llid=y.llid) WHERE y.stamp = (SELECT MAX(stamp) FROM station_status);");
  return json_encode($data);
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

/*
  * Front end functions
  * These are used by the template pages
*/

/*
  * Compare stations based on the diff docks
  * (The difference between the max and min number of  docks available over a time period)
  * Used with usort
*/
function diffockcmp($a, $b) { return $a->diffDocks > $b->diffDocks; }

/*
  * Used for filtering by different station station keys.
*/
function status1($x) { return ($x->status == '1'); }
function status2($x) { return ($x->status == '2'); }
function status3($x) { return ($x->status == '3'); }

/**
 * Echo a list items for stations. Give special formatting to those with diffDocks=0
 * @param array $data An array of objects. Each object describes a stations, with an id, name, and dock information
 * @param string $line Formatting string for arranging the link, stationId, stationName, maxDocks and minDocks
 * @param string $link the link to info about each station, will be appended to the bloginfo->home.
 * 
 * @return string A html list.
 *
*/
function station_list($data, $line='%s (%s, %s)', $link='/station-dashboard/?station=') {
    $output = '';
    foreach ($data as $value):
        if ($value->diffDocks > 0):
            $format = $line;
        else:
            $format = '<span class="badge badge-important">' . $line . '</span>';
        endif;

        $format = '<a href="%s'. $link .'%s">'. $format .'</a>';

        $output .= "  <li>". sprintf($format, get_bloginfo('home'), $value->id, $value->stationName, $value->maxDocks, $value->minDocks) ."</li>\n";

    endforeach;
    return $output;
}

/**
  * return either the singular or plural of a number
*/
function pluralize($x, $singular='hour', $plural='hours') {
    return ($x == 1) ? $singular : $x . ' '.$plural;
}
?>