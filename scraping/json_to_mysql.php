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

function json_to_mysql($f, $host, $user, $pword, $database) {

  /* constants */
  $INSERT_ROW = 'INSERT INTO station_status (station_id, availableDocks, totalDocks, statusValue, statusKey, availableBikes, lastCommunicationTime, stamp) VALUES (:id, :availableDocks, :totalDocks, :statusValue, :statusKey, :availableBikes, :lastCommunicationTime, :stamp)';
  $INSERT_STATION = 'INSERT INTO stations (id, stationName, latitude, longitude, stAddress1, stAddress2, city, postalCode, location, altitude, landMark) VALUES (:id, :stationName, :latitude, :longitude, :stAddress1, :stAddress2, :city, :postalCode, :location, :altitude, :landMark)';

  $insert_row_keys = array(
    "id" => NULL,
    "availableDocks" => NULL,
    "totalDocks" => NULL,
    "statusValue" => NULL,
    "statusKey" => NULL,
    "availableBikes" => NULL,
    "lastCommunicationTime" => NULL,
    "stamp" => NULL
  );

  $insert_station_keys = array(
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
  );

  /// Open the JSON.
  try {
    $handle = fopen($f, 'r');
    $data = fread($handle, filesize($f));
    $data = (array) json_decode($data);
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

  // Organize the data and date
  $stats = $data['stationBeanList'];

  date_default_timezone_set("America/New_York");
  $dateObj = new DateTime($data['executionTime']);
  $timestamp = $dateObj->format('Y-m-d H:i:s');

  try {
    // Check if this timestamp has been added already
    $select_count = $pdo->prepare("SELECT COUNT(id) count FROM station_status WHERE stamp='". $timestamp . "';");
    $select_count->execute();
    $select_count->setFetchMode(PDO::FETCH_ASSOC);
    $result_count = $select_count->fetch();

  } catch (PDOException $e) {
    echo $e->getMessage() ."\n";
  }

  if ($result_count['count'] > 0):
    echo 'skipping '. $timestamp .' '. $f ."\n";
    return;
  endif;

  // Get list of current station IDs, for inserting (or not) stations
  try {
    $select_stn = $pdo->prepare("SELECT id FROM stations");
    $select_stn->execute();
    $select_stn->setFetchMode(PDO::FETCH_ASSOC);
    $result_stations = $select_stn->fetchAll();
  } catch (PDOException $e) {
    print $e->getMessage() ."\n";
  }

  $station_ids = array();
  foreach ($result_stations as $stn):
    $station_ids[] = $stn['id'];
  endforeach;
  // echo 'found stations: '. count($station_ids);

  // Loop through JSON, formatting data a bit and inserting.
  foreach ($stats as $row):
    $row = (array) $row;
    $row['lastCommunicationTime'] = ($row['lastCommunicationTime'] === '') ? 'NULL' : $row['lastCommunicationTime'];
    $row['stamp'] = $timestamp;
    // $row['stationName'] = row['stationName'].encode('ascii', 'ignore')
    // $row['stAddress1'] = row['stAddress1'].encode('ascii', 'ignore')

    $row_data = array_intersect_key($row, $insert_row_keys);

    try {
      $insert_row = $pdo->prepare($INSERT_ROW);
      $insert_row->execute($row_data);
    } catch (PDOException $e) {
      echo $e->getMessage() ."\n";
      echo $insert_row->queryString;
      echo $insert_row->debugDumpParams();
      echo 'row data' . count($row_data) . "\n";
      // var_dump($row_data);
      echo 'insert_row_keys ' .count($insert_row_keys) . "\n";
      // var_dump($insert_row_keys);
    }
    // echo sprintf("Auto Increment ID: %s", $pdo->lastInsertId);

    // Check if that station is in the stations table
    if (!in_array($row['id'], $station_ids)):
      // If not, fiddle with the data
      $row['altitude'] = ($row['altitude'] === '' || $row['altitude'] === NULL) ? '' : $row['altitude'];

      $station_data = array_intersect_key($row, $insert_station_keys);
      try {
        $insert_stn = $pdo->prepare($INSERT_ROW);
        $insert_stn->execute($row);
        // echo 'inserted station '. $row['id'];
      } catch (PDOException $e) {
        echo $e->getMessage() . "\n";
        echo 'problem inserting station '. $station_data['id'] ."\n";
        echo $insert_stn->queryString;
        // echo $insert_stn->debugDumpParams();
        echo 'station data ' . count($station_data) . "\n";
        echo 'station ' .count($insert_station_keys) . "\n";
        
      }
    endif;

  endforeach;

  // Close the connection
  $pdo = NULL;

}
?>