<?php
#!/usr/bin/php
include 'json_to_mysql.php';
include 'bikeshare_config.php';
$f = $argv[1]; // CLI argument
json_to_mysql($f, $HOST, $USER, $PWORD, $DATABASE);
?>
