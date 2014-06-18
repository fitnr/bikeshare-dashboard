<?php
#!/usr/bin/php
/*
  Scrapes a file from the bikeshare site.
  Then processes into mySQL.
*/
include 'functions.php';
include 'config.php';

// Use CLI argument if it exists
if (array_key_exists(1, $argv)):
  $feed = $argv[1];
else:
  $feed = $JSONFEED;
endif;

$data = scrape_json($feed);
json_to_mysql($data, $HOST, $USER, $PWORD, $DATABASE);

?>
