<?php
/**
 * The base configurations of the <thing>
 *
 *
 *
 * @package bikeshare-dashboard
 */

// base URL of the thing we are at!
// No http or https needed
define('SITE_URL', 'localhost');

define('BASE_DIR', 'bikeshare-dashboard/www');

// ** MySQL settings - You can get this info from your web host ** //
define('DB_NAME', '');

/** MySQL database username */
define('DB_USER', '');

/** MySQL database password */
define('DB_PASSWORD', '');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

define('TIMEZONE', 'America/New_York');

if ( !defined('ABSPATH') )
  define('ABSPATH', dirname(dirname(__FILE__) . '/'));
