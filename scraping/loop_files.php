<?php
require 'function.php';
require 'config.php';

foreach (scandir($DIR) as $path): // Loop saved files

  if ($path == '.' || $path == '..')
    continue;

  json_to_mysql($DIR.$path, $HOST, $USER, $PWORD, $DATABASE);
endforeach;
?>
