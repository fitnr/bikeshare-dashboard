<?php
/*
  one row looks like this:

  "id":72,
  "stationName":"W 52 St & 11 Av",
  "availableDocks":28,
  "totalDocks":39,
  "latitude":40.76727216,
  "longitude":-73.99392888,
  "statusValue":"In Service",
  "statusKey":1,
  "availableBikes":7,
  "stAddress1":"W 52 St & 11 Av",
  "stAddress2":"",
  "city":"","postalCode":"",
  "location":"","altitude":"",
  "testStation":false,
  "lastCommunicationTime":null,
  "landMark":""

  station_status looks like:
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `station_id` int(11) NOT NULL,
    `availableDocks` smallint(5) unsigned DEFAULT NULL,
    `totalDocks` smallint(5) unsigned DEFAULT NULL,
    `statusValue` varchar(128) DEFAULT NULL,
    `statusKey` tinyint(1) DEFAULT NULL,
    `availableBikes` smallint(5) unsigned DEFAULT NULL,
    `lastCommunicationTime` datetime DEFAULT NULL,
    `stamp` datetime DEFAULT NULL,

  stations looks like:
    `id` int(10) unsigned NOT NULL,
    `stationName` varchar(128) DEFAULT NULL,
    `latitude` double(10,8) DEFAULT NULL,
    `longitude` double(10,8) DEFAULT NULL,
    `stAddress1` varchar(256) DEFAULT NULL,
    `stAddress2` varchar(256) DEFAULT NULL,
    `city` varchar(128) DEFAULT NULL,
    `postalCode` varchar(128) DEFAULT NULL,
    `location` varchar(128) DEFAULT NULL,
    `altitude` int(11) DEFAULT NULL,
    `landMark` varchar(128) DEFAULT NULL,
    `communityboard` smallint(3) unsigned DEFAULT NULL,
*/

/* constants*/
$queries = array(
  "INSERT_ROW" => 'INSERT INTO station_status (station_id, availableDocks, totalDocks, statusValue, statusKey, availableBikes, lastCommunicationTime, stamp) VALUES (:id, :availableDocks, :totalDocks, :statusValue, :statusKey, :availableBikes, :lastCommunicationTime, :stamp)',
  "INSERT_STATION" => 'INSERT INTO stations (id, stationName, latitude, longitude, stAddress1, stAddress2, city, postalCode, location, altitude, landMark) VALUES (:id, :stationName, :latitude, :longitude, :stAddress1, :stAddress2, :city, :postalCode, :location, :altitude, :landMark)',
  // 1 is the geoid for all of NYC
  "INSERT_STATUS" => "INSERT INTO status_report
  (stamp, geo_id, availBikes, availDocks, nullDocks, totalDocks, fullStations, emptyStations, plannedStations, inactiveStations)
  SELECT
  stamp, 1 geo_id,
  SUM(IF(statusKey=1, availableBikes, NULL)) availBikes,
  SUM(IF(statusKey=1, availableDocks, NULL)) availDocks,
  SUM(IF(statusKey=1, totalDocks-availableDocks-availableBikes, NULL)) nullDocks,
  SUM(IF(statusKey=1, totalDocks, NULL)) totalDocks,
  COUNT(IF(statusKey=1 AND availableDocks=0, 1, NULL)) fullStations,
  COUNT(IF(statusKey=1 AND availableBikes=0, 1, NULL)) emptyStations,
  COUNT(IF(statusKey=2, 1, NULL)) plannedStations,
  COUNT(IF(statusKey=3, 1, NULL)) inactiveStations
  FROM station_status WHERE stamp=:stamp"
  );

$param_keys = array(

  'insert_row_keys' => array(
    "id" => NULL,
    "availableDocks" => NULL,
    "totalDocks" => NULL,
    "statusValue" => NULL,
    "statusKey" => NULL,
    "availableBikes" => NULL,
    "lastCommunicationTime" => NULL,
    "stamp" => NULL
  ),

  'insert_station_keys' => array(
    "id" => NULL,
    "stationName" => NULL,
    "latitude" => NULL,
    "longitude" => NULL,
    "stAddress1" => NULL,
    "stAddress2" => NULL,
    "city" => NULL,
    "postalCode" => NULL,
    "location" => NULL,
    "altitude" => NULL,
    "landMark" => NULL
  )
  );

function json_to_mysql($f, $host, $user, $pword, $database, $tz="America/New_York") {

  date_default_timezone_set($tz);

  try {
    $data = open_file($f);
    list($stats, $timestamp) = parse_data($data);
  } catch (Exception $e) {
    echo $e->getMessage() ."\n";
    return;
  }

  // Connect to the DB.
  try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $user, $pword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (PDOException $e) {
    echo $e->getMessage() ."\n";
  }

  // Check if this timestamp has been added already
  $result_count = count_timestamp($timestamp, $pdo);
  if ($result_count > 0):
    echo 'Skipping '. $timestamp .', seen it before. Found in '. $f ."\n";
    return;
  endif;

  // Get list of current station IDs, for inserting (or not) stations
  $station_ids = get_station_ids($pdo);
  // echo 'found stations: '. count($station_ids);

  // Loop through JSON, formatting data a bit and inserting.
  foreach ($stats as $row):
    $row = (array) $row;
    $row['stamp'] = $timestamp;
    insert_row($row, $pdo);

    // Check if that station is in the stations table
    if (!in_array($row['id'], $station_ids))
      insert_station($row, $pdo);

  endforeach;

  // create a new row in the status table
  update_status_table($timestamp, $pdo);

  // Close the connection
  $pdo = NULL;
}

function open_file($f) {
  $fs = filesize($f);

  if ($fs == 0)
    throw new Exception("File empty or missing: " . $f, 1);

  $data = fread(fopen($f, 'r'), $fs);
  return (array) json_decode($data);
}

function parse_data($data) {
  // Organize the data, set the date
  if (isset($data['stationBeanList']) && isset($data['executionTime'])):
    $stats = $data['stationBeanList'];
    $d = new DateTime($data['executionTime']);
    $timestamp = $d->format('Y-m-d H:i:s');
  else:
    throw new Exception("Missing or badly formatted data in " . $f, 1);
  endif;

  return array($stats, $timestamp);
}

function count_timestamp($timestamp, $pdo) {
  try {
    $select_count = $pdo->prepare("SELECT COUNT(*) count FROM station_status WHERE stamp=:stamp;");
    $select_count->execute(array('stamp'=>$timestamp));
    $select_count->setFetchMode(PDO::FETCH_ASSOC);
    $rc = $select_count->fetch();
    return $rc['count'];

  } catch (PDOException $e) {
    echo $e->getMessage() ."\n";
  }
}

function get_station_ids($pdo) {
  $station_ids = array();

  try {
    $select_stn = $pdo->prepare("SELECT id FROM stations");
    $select_stn->execute();
    $select_stn->setFetchMode(PDO::FETCH_ASSOC);
    $result_stations = $select_stn->fetchAll();
  } catch (PDOException $e) {
    print $e->getMessage() ."\n";
  }
  foreach ($result_stations as $stn)
    $station_ids[] = $stn['id'];

  return $station_ids;
}

function insert_row($row, $pdo) {
  global $queries, $param_keys;

  // fiddle with the data
  $row['lastCommunicationTime'] = ($row['lastCommunicationTime'] === '') ? 'NULL' : $row['lastCommunicationTime'];

  $row_data = array_intersect_key($row, $param_keys['insert_row_keys']);

  try {
    $insert_row = $pdo->prepare($queries['INSERT_ROW']);
    $insert_row->execute($row_data);
  } catch (PDOException $e) {
    echo $e->getMessage() ."\n";
    echo $insert_row->queryString;
    // echo $insert_row->debugDumpParams();
    // echo 'row data' . count($row_data) . "\n";
    // var_dump($row_data);
    // echo 'insert_row_keys ' .count($insert_row_keys) . "\n";
    // var_dump($insert_row_keys);
  }
  // echo sprintf("Auto Increment ID: %s", $pdo->lastInsertId);
}

function insert_station($row, $pdo) {
  // Fiddle with the data
  $row['altitude'] = ($row['altitude'] === '' || $row['altitude'] === NULL) ? '' : $row['altitude'];

  $station_data = array_intersect_key($row, $param_keys['insert_station_keys']);
  try {
    $insert_stn = $pdo->prepare($queries['INSERT_STATION']);
    $insert_stn->execute($station_data);
    // echo 'inserted station '. $row['id'];
  } catch (PDOException $e) {
    echo $e->getMessage() . "\n";
    echo 'problem inserting station '. $station_data['id'] ."\n";
    echo $insert_stn->queryString;
    // echo $insert_stn->debugDumpParams();
    // echo 'stn data ' . count($station_data) . "\n";
    // echo 'station  ' .count($insert_station_keys) . "\n";
    
  }
}

function update_status_table($timestamp, $pdo) {
  global $queries, $param_keys;

  try {
    $insert_status = $pdo->prepare($queries['INSERT_STATUS']);
    $insert_status->execute(array('stamp'=>$timestamp));
  } catch (PDOException $e) {
    echo $e->getMessage() . "\n";
    echo $insert_status->queryString;
  }
}
?>