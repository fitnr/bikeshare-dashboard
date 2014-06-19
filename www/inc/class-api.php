<?php
include 'config.php';
include 'class-db.php';
/*
 * Add api functions for wordpress to respond to.
*/
class api {

  private $query_vars = array();

  function __construct($get, $db=False, $dbuser='', $dbpassword='', $dbname='', $dbhost='') {
    if ($db === False):
      $this->db = new db($dbuser, $dbpassword, $dbname, $dbhost);
    else:
      $this->db = $db;
    endif;

    $this->query_vars = $get;

    if (preg_match(',/system_activity/,', $_SERVER['REQUEST_URI'])) {
      header("HTTP/1.0 200 OK");
      $args = $this->get_overview_data();
      echo $this->abstract_bikeshare_dashboard($args);
      exit;
      
    } elseif (preg_match(',/system_activity_csv/,', $_SERVER['REQUEST_URI'])) {
      $args = $this->get_overview_data();
      $args['output'] = 'csv';

      $args['fileName'] = 'station-activity-overview';
      // $args['fileName'] = date('%o-%m-%d %G:%i') . '-station-activity-overview';

      $output = $this->abstract_bikeshare_dashboard($args);
      header("HTTP/1.0 200 OK");
      header('Content-type: application/CSV');
      header('Content-Disposition: attachment; filename="'. $output['filename'] .'.csv"');
      header("Pragma: no-cache");
      header("Expires: 0");
      echo $output['csv'];
      exit;

    } elseif (preg_match('-/station_activity/(\d+)/-', $_SERVER['REQUEST_URI'], $matches)) {
      header("HTTP/1.0 200 OK");
      echo $this->station_activity($matches[1]);
      exit;

    } elseif (preg_match('-/station_trips/(\d+)/-', $_SERVER['REQUEST_URI'], $matches)) {
      header("HTTP/1.0 200 OK");
      echo $this->station_trips($matches[1]);
      exit;

    } elseif (preg_match('-/station_activity_csv/(\d+)/-', $_SERVER['REQUEST_URI'], $matches)) {
      $output = $this->station_activity($matches[1], 'csv');
      header("HTTP/1.0 200 OK");
      header('Content-type: application/CSV');
      header('Content-Disposition: attachment; filename="'. $output['filename'] .'.csv"');
      header("Pragma: no-cache");
      header("Expires: 0");
      echo $output['csv'];
      exit;

    } else if (preg_match(',/station_pattern/(\d+)/,', $_SERVER['REQUEST_URI'], $matches)) {
      header("HTTP/1.0 200 OK");
      echo $this->get_station_pattern($matches[1]);
      exit;

    } else if (preg_match(',/get_station_locations/?$,', $_SERVER['REQUEST_URI'])) {
      header("HTTP/1.0 200 OK");
      echo $this->station_locations();
      exit;
    }
  }

  function get_query_var($var, $default=0) {
    return isset($this->query_vars[$var]) && $this->query_vars[$var] != NULL ? $this->query_vars[$var] : $default;
  }

  function abstract_bikeshare_dashboard($kwargs, $output='data') {
    $query = $this->db->prepare($kwargs['q'], $kwargs['since']);
    $data = $this->db->get_results($query);

    if (!$data)
      $this->db->print_error();

    if ($kwargs['output'] == 'csv' || $output == 'csv'):
      return $this->output_csv($kwargs['fileName'], $data);

    elseif ($kwargs['output'] == 'json' || $output == 'json'):
      return json_encode($data);

    else:
      return $data;
    endif;
  }

  function get_station_meta($id) { // Internal function, not in API
    // cast is workaround for 32 bit systems that can't handle BIG OL' integers.
    $sql = $this->db->prepare("SELECT s.stationName, r.totalDocks from stations s JOIN station_status r ON s.llid=r.llid where s.llid = CAST(%s AS UNSIGNED INTEGER) LIMIT 1", $id);
    $data = $this->db->get_row($sql);
    $data->sql = $sql;
    return $data;
  }

  // Get overview information for the whole system
  function get_overview_data($since=6) {
    // $since is in hours
    
    if (isset($this->query_vars['since'])):
       $since = $this->query_vars['since'];
    endif;

    $q = "SELECT s.stamp datetime, s.totalDocks Total_Docks, s.availDocks Available_Docks, s.availBikes Available_Bikes, s.nullDocks Null_Docks, s.fullStations Full_Stations, s.plannedStations Planned_Stations, s.emptyStations Empty_Stations, s.inactiveStations Inactive_Stations FROM status_report s WHERE (s.stamp > NOW() - INTERVAL %d HOUR) ORDER BY s.stamp ASC;";
    return array('q'=>$q, 'since'=>$since, 'filename'=>'overview-' . date('Y-m-d'), 'output'=>'json');
  }

  // Get day-by-day average data for this station over the last interval.
  // Since is in Days
  function get_station_pattern($llid, $ouput='json', $since=28) {
    $d = new DateTime();
    // Subtract days from the current date to get the most recent Sunday
    $d = $d->modify('-' . $d->format('w') .' day');
    $recentSunday = $d->format('Y-m-j');
    $q = "SELECT CONCAT(DATE_SUB(%s, INTERVAL WEEKDAY(s.stamp) DAY), ' ', LPAD(HOUR(s.stamp), 2, '0'), ':', LPAD(FLOOR(MINUTE(s.stamp)/30)*30, 2, '0')) datetime, ROUND(AVG(s.availableDocks), 2) avgDocks, ROUND(AVG(s.availableBikes), 2) avgBikes, ROUND(AVG(s.totalDocks - s.availableBikes - s.availableDocks), 2) avgNullDocks FROM station_status s WHERE s.llid=%d AND (s.stamp > NOW() - INTERVAL %d DAY) GROUP BY datetime;";
    $data = $this->db->get_results($this->db->prepare($q, $recentSunday, $llid, $since));
    return json_encode($data);
  }

  // Get information about the status of each station in the system
  function station_overview($since=1) {
    // $since is in hours
    if(isset($this->query_vars['since'])):
      $since = $this->query_vars['since'];
    endif;

    $q = "SELECT MIN(x.availableDocks) minDocks, MAX(x.availableDocks) maxDocks, MAX(x.availableDocks)-MIN(x.availableDocks) diffDocks, MAX(x.statusKey) status, x.llid id, y.stationName FROM station_status x JOIN stations y ON x.llid = y.llid WHERE (x.stamp > NOW() - INTERVAL %d HOUR) GROUP BY x.llid";
    return array(
      'q'=>$q,
      'since' => $since
    );
  }

  function get_stationname($llid) {
    // cast is workaround for 32 bit systems that can't handle BIG OL' integers.
    $j = "SELECT y.stationName FROM stations y WHERE y.llid=CAST(%s AS UNSIGNED INTEGER)";
    return $this->db->get_var($this->db->prepare($j, $llid));
  }

  // The status of a particular station over time.
  function station_activity($llid, $output='json') {

    list($interval_end, $interval_length) = $this->prepare_startstop_intervals();

    $filter = '';
    if ($interval_length > 12) :
      // $filter = ' AND MINUTE(stamp) % 2 = 0';
      // $filter = " AND MINUTE(stamp) % 2 = 0"; // Get records from only even minutes
    endif;
      
    // elseif ($interval_length > 36) :
    //   $filter = " AND MINUTE(stamp) % 3 = 0"; // Get records from every third minute
    // elseif ($interval_length > 72) :
    //   $filter = " AND MINUTE(`stamp`) % 5 = 0"; // Get records from every fifth minute
    // endif;

    $q = "SELECT stamp datetime, availableDocks Available_Docks, availableBikes Available_Bikes, (totalDocks - availableDocks - availableBikes) Null_Docks
      FROM station_status WHERE llid=CAST(%s AS UNSIGNED INTEGER)
      AND (`stamp` < ". $interval_end .") AND (`stamp` > ". $interval_end ." - INTERVAL %d HOUR) ". $filter ." ORDER BY `stamp` ASC";

    $activity_data = $this->db->get_results($this->db->prepare($q, $llid, $interval_length));

    if (!$activity_data)
      return json_encode(array("query"=>$q, 'end'=>$interval_end, 'len'=>$interval_length));

    if ($output=='csv'):
      $stationName = $this->get_stationname($llid);
      return $this->output_csv($stationName, $activity_data);
    elseif ($activity_data) :
      return json_encode($activity_data);
    endif;
  }

  // Get trips for a station over time.
  function station_trips($llid, $output="json") {

    list($interval_end, $interval_length) = $this->prepare_startstop_intervals();

    $query = "SELECT s.datetime, s.count starts, e.count ends FROM (
      SELECT CONCAT(YEAR(`starttime`), '-', LPAD(MONTH(`starttime`), 2, 0), '-', LPAD(DAY(`starttime`), 2, 0), ' ', LPAD(HOUR(`starttime`), 2, 0), ':00:00') datetime,
      count(*) count
      FROM trips t WHERE t.`startid` = CAST(%s AS UNSIGNED INTEGER)
      AND t.`starttime` < ". $interval_end ."
      AND t.`starttime` > ". $interval_end ." - INTERVAL %d HOUR
      AND t.`usertype` != ''
      GROUP BY MONTH(`starttime`), DAY(`starttime`), HOUR(`starttime`)
      ORDER BY `starttime` ASC
      ) s, (
      SELECT CONCAT(YEAR(`endtime`), '-', LPAD(MONTH(`endtime`), 2, 0), '-', LPAD(DAY(`endtime`), 2, 0), ' ', LPAD(HOUR(`endtime`), 2, 0), ':00:00') datetime,
      count(*) count
      FROM trips t WHERE t.`endid` = CAST(%s AS UNSIGNED INTEGER)
      AND t.`endtime` < ". $interval_end ."
      AND t.`endtime` > ". $interval_end ." - INTERVAL %d HOUR
      AND t.`usertype` != ''
      GROUP BY MONTH(`endtime`), DAY(`endtime`), HOUR(`endtime`)
      ORDER BY `endtime` ASC
      ) e WHERE e.datetime=s.datetime;";

    $query = $this->db->prepare($query, $llid, $interval_length, $llid, $interval_length);
    $query = sprintf($query, $llid, $interval_length, $llid, $interval_length);
    $data = $this->db->get_results($query);

    if (!$data)
      return json_encode(array("query"=>$query, 'end'=>$interval_end, 'llid'=>$llid, 'len'=>$interval_length));

    if ($output=='csv'):
      $stationName = get_stationname($llid);
      return $this->output_csv($stationName, $data);
    else:
      return json_encode($data);
    endif;
  }

  function prepare_startstop_intervals() {
    // No variables set
    if (!isset($this->query_vars['starttime']) && !isset($this->query_vars['stoptime'])) :
      return array('NOW()', 24);

    // either start or stop
    elseif (isset($this->query_vars['stoptime']) || isset($this->query_vars['starttime'])) :
      $start = new DateTime($this->query_vars['starttime'], new DateTimeZone('America/New_York'));
      $stop  = new DateTime($this->query_vars['endtime'],  new DateTimeZone('America/New_York'));  

      // Check if time is in the future
      $now = new DateTime('now', new DateTimeZone('America/New_York'));
      if ($stop->diff($now)->invert)
        return array('NOW()', 24);

      $interval_end = $stop->format("'Y-m-d H:i'");

      // check if times are the same
      if ($interval_end == $start->format("'Y-m-d H:i'"))
        return array($interval_end, 24);

      // Difference in hours. No more than 2 weeks.
      $diff = $start->diff($stop);
      $diff_hours = ($diff->format("%d") * 24) + $diff->format('%h') + ($diff->format('%i') / 60) ;
      $diff_hours = min(336, $diff_hours);

      return array($interval_end, $diff_hours);
    endif;
  }

  function station_locations(){
    $data = $this->db->get_results("SELECT x.llid id, x.stationName, x.latitude lat, x.longitude lon, y.availableDocks, y.availableBikes, y.totalDocks, IF(statusKey=1 AND availableDocks=0,1,0) fullFlag, IF(statusKey=1 AND availableBikes=0,1,0) emptyFlag, statusValue FROM stations x INNER JOIN station_status y ON (x.llid=y.llid) WHERE y.stamp = (SELECT MAX(stamp) FROM station_status);");
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
}
?>