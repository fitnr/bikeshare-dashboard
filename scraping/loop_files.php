<?php
require 'function.php';
require '../config.php';

/* 
	* Used for scraping from saved files.
*/

foreach (scandir($DIR) as $path): // Loop saved files

  if ($path == '.' || $path == '..')
    continue;

  json_to_mysql($DIR.$path, DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
endforeach;
?>
