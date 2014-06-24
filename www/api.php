<?php
/**
 * @package bikeshare-dashboard
*/

include '../config.php';
include 'inc/functions.php';

new api($_GET, False, DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
