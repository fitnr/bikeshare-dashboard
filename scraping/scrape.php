<?php
#!/usr/bin/php
/*
  Scrapes a file from the bikeshare site.
  Then processes into mySQL.
*/

include 'functions.php';
include '../config.php';

// Use CLI argument if it exists
if (array_key_exists(1, $argv)):
  $feed = $argv[1];
else:
  $feed = JSONFEED;
endif;

// Read the data from the web
$data = curl_file($feed);

// attempt to insert into mysql DB
// If anything goes wrong, save the file for later
try {

  $json = parse_json($data);
  json_to_mysql($json, DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);  

} catch (Exception $e) {

  savefile($json);

}

?>
