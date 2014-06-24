<?php
include '../config.php';
include 'inc/functions.php';

$cms = new cms('dashboard-bikeshare');

if ($cms):
  include $cms->display($template);
else:
  header("HTTP/1.0 404 NOT FOUND");
endif;

?>