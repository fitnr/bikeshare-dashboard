<?php

class db {
  /**
   * Whether MySQL is used as the database engine.
   *
   * Set in WPDB::db_connect() to true, by default. This is used when checking
   * against the required MySQL version for WordPress. Normally, a replacement
   * database drop-in (db.php) will skip these checks, but setting this to true
   * will force the checks to occur.
   *
   * @since 3.3.0
   * @access public
   * @var bool
   */
  public $is_mysql = null;

  protected $dbuser;
  protected $dbpassword;
  protected $dbname;
  protected $dbhost;
  protected $dbh;

  var $func_call;

  function __construct( $dbuser, $dbpassword, $dbname, $dbhost) {

    register_shutdown_function( array( $this, '__destruct' ) );

    if ( _DEBUG && _DEBUG_DISPLAY )
      $this->show_errors();

    $this->init_charset();

    $this->dbuser = $dbuser;
    $this->dbpassword = $dbpassword;
    $this->dbname = $dbname;
    $this->dbhost = $dbhost;

    $this->db_connect();
  }

  /**
   * PHP5 style destructor and will run when database object is destroyed.
   *
   * @see wpdb::__construct()
   * @since 2.0.8
   * @return bool true
   */
  function __destruct() {
    return true;
  }

  /**
   * Connect to and select database
   *
   * @since 3.0.0
   */
  function db_connect() {

    $this->is_mysql = true;

    $new_link = defined( 'MYSQL_NEW_LINK' ) ? MYSQL_NEW_LINK : true;
    $client_flags = defined( 'MYSQL_CLIENT_FLAGS' ) ? MYSQL_CLIENT_FLAGS : 0;

    if ( _DEBUG ) {
      $error_reporting = false;
      if ( defined( 'E_DEPRECATED' ) ) {
        $error_reporting = error_reporting();
        error_reporting( $error_reporting ^ E_DEPRECATED );
      }
      $this->dbh = mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, $new_link, $client_flags );
      if ( false !== $error_reporting ) {
        error_reporting( $error_reporting );
      }
    } else {
      $this->dbh = @mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, $new_link, $client_flags );
    }

    if ( !$this->dbh ) {
      $this->bail( sprintf( "<h1>Error establishing a database connection</h1>
      <p>This either means that the username and password information in your <code>wp-config.php</code> file is incorrect or we can't contact the database server at <code>%s</code>. This could mean your host's database server is down.</p>
      <ul>
        <li>Are you sure you have the correct username and password?</li>
        <li>Are you sure that you have typed the correct hostname?</li>
        <li>Are you sure that the database server is running?</li>
      </ul>
      ", htmlspecialchars( $this->dbhost, ENT_QUOTES ) ), 'db_connect_fail' );

      return;
    }

    $this->set_charset( $this->dbh );

    $this->ready = true;

    $this->select( $this->dbname, $this->dbh );
  }

  /**
   * Escapes content by reference for insertion into the database, for security
   *
   * @uses wpdb::_real_escape()
   * @since 2.3.0
   * @param string $string to escape
   * @return void
   */
  function escape_by_ref( &$string ) {
    if ( ! is_float( $string ) )
      $string = $this->_real_escape( $string );
  }

  /**
   * Real escape, using mysql_real_escape_string()
   *
   * @see mysql_real_escape_string()
   * @since 2.8.0
   * @access private
   *
   * @param  string $string to escape
   * @return string escaped
   */
  function _real_escape( $string ) {
    if ( $this->dbh )
      return mysql_real_escape_string( $string, $this->dbh );

    $class = get_class( $this );
    _doing_it_wrong( $class, "$class must set a database connection for use with escaping.", E_USER_NOTICE );
    return addslashes( $string );
  }

  /**
   * Enables showing of database errors.
   *
   * This function should be used only to enable showing of errors.
   * wpdb::hide_errors() should be used instead for hiding of errors. However,
   * this function can be used to enable and disable showing of database
   * errors.
   *
   * @since 0.71
   * @see wpdb::hide_errors()
   *
   * @param bool $show Whether to show or hide errors
   * @return bool Old value for showing errors.
   */
  function show_errors( $show = true ) {
    $errors = $this->show_errors;
    $this->show_errors = $show;
    return $errors;
  }

  /**
   * Print SQL/DB error.
   *
   * @since 0.71
   *
   * @param string $str The error to display
   * @return bool False if the showing of errors is disabled.
   */
  function print_error( $str = '' ) {
    if ( !$str )
      $str = mysql_error( $this->dbh );

    if ( $this->suppress_errors )
      return false;

    $error_str = sprintf( 'database error %1$s for query %2$s', $str, $this->last_query );

    error_log( $error_str );

    // Are we showing errors?
    if ( ! $this->show_errors )
      return false;

    $str   = htmlspecialchars( $str, ENT_QUOTES );
    $query = htmlspecialchars( $this->last_query, ENT_QUOTES );

    print "<div id='error'>
      <p class='wpdberror'><strong>database error:</strong> [$str]<br />
      <code>$query</code></p>
      </div>";
  }

  /**
   * Set $this->charset and $this->collate
   *
   * @since 3.1.0
   */
  function init_charset() {
    if ( function_exists('is_multisite') && is_multisite() ) {
      $this->charset = 'utf8';
      if ( defined( 'DB_COLLATE' ) && DB_COLLATE )
        $this->collate = DB_COLLATE;
      else
        $this->collate = 'utf8_general_ci';
    } elseif ( defined( 'DB_COLLATE' ) ) {
      $this->collate = DB_COLLATE;
    }

    if ( defined( 'DB_CHARSET' ) )
      $this->charset = DB_CHARSET;
  }

  /**
   * Sets the connection's character set.
   *
   * @since 3.1.0
   *
   * @param resource $dbh     The resource given by mysql_connect
   * @param string   $charset The character set (optional)
   * @param string   $collate The collation (optional)
   */
  function set_charset( $dbh, $charset = null, $collate = null ) {
    if ( ! isset( $charset ) )
      $charset = $this->charset;
    if ( ! isset( $collate ) )
      $collate = $this->collate;
    if ( $this->has_cap( 'collation' ) && ! empty( $charset ) ) {
      if ( function_exists( 'mysql_set_charset' ) && $this->has_cap( 'set_charset' ) ) {
        mysql_set_charset( $charset, $dbh );
      } else {
        $query = $this->prepare( 'SET NAMES %s', $charset );
        if ( ! empty( $collate ) )
          $query .= $this->prepare( ' COLLATE %s', $collate );
        mysql_query( $query, $dbh );
      }
    }
  }

  /**
   * Determine if a database supports a particular feature.
   *
   * @since 2.7.0
   * @see wpdb::db_version()
   *
   * @param string $db_cap The feature to check for.
   * @return bool
   */
  function has_cap( $db_cap ) {
    $version = $this->db_version();

    switch ( strtolower( $db_cap ) ) {
      case 'collation' :    // @since 2.5.0
      case 'group_concat' : // @since 2.7.0
      case 'subqueries' :   // @since 2.7.0
        return version_compare( $version, '4.1', '>=' );
      case 'set_charset' :
        return version_compare( $version, '5.0.7', '>=' );
    };

    return false;
  }

  /**
   * The database version number.
   *
   * @since 2.7.0
   *
   * @return false|string false on failure, version number on success
   */
  function db_version() {
    return preg_replace( '/[^0-9.].*/', '', mysql_get_server_info( $this->dbh ) );
  }

  /**
   * Wraps errors in a nice header and footer and dies.
   *
   * Will not die if wpdb::$show_errors is false.
   *
   * @since 1.5.0
   *
   * @param string $message The Error message
   * @param string $error_code Optional. A Computer readable string to identify the error.
   * @return false|void
   */
  function bail( $message, $error_code = '500' ) {
    if ( !$this->show_errors ) {
      if ( class_exists( 'WP_Error' ) )
        $this->error = new WP_Error($error_code, $message);
      else
        $this->error = $message;
      return false;
    }
    die($message);
  }

  /**
   * Prepares a SQL query for safe execution. Uses sprintf()-like syntax.
   *
   * The following directives can be used in the query format string:
   *   %d (integer)
   *   %f (float)
   *   %s (string)
   *   %% (literal percentage sign - no argument needed)
   *
   * All of %d, %f, and %s are to be left unquoted in the query string and they need an argument passed for them.
   * Literals (%) as parts of the query must be properly written as %%.
   *
   * This function only supports a small subset of the sprintf syntax; it only supports %d (integer), %f (float), and %s (string).
   * Does not support sign, padding, alignment, width or precision specifiers.
   * Does not support argument numbering/swapping.
   *
   * May be called like {@link http://php.net/sprintf sprintf()} or like {@link http://php.net/vsprintf vsprintf()}.
   *
   * Both %d and %s should be left unquoted in the query string.
   *
   * <code>
   * wpdb::prepare( "SELECT * FROM `table` WHERE `column` = %s AND `field` = %d", 'foo', 1337 )
   * wpdb::prepare( "SELECT DATE_FORMAT(`field`, '%%c') FROM `table` WHERE `column` = %s", 'foo' );
   * </code>
   *
   * @link http://php.net/sprintf Description of syntax.
   * @since 2.3.0
   *
   * @param string $query Query statement with sprintf()-like placeholders
   * @param array|mixed $args The array of variables to substitute into the query's placeholders if being called like
   *  {@link http://php.net/vsprintf vsprintf()}, or the first variable to substitute into the query's placeholders if
   *  being called like {@link http://php.net/sprintf sprintf()}.
   * @param mixed $args,... further variables to substitute into the query's placeholders if being called like
   *  {@link http://php.net/sprintf sprintf()}.
   * @return null|false|string Sanitized query string, null if there is no query, false if there is an error and string
   *  if there was something to prepare
   */
  function prepare( $query, $args ) {
    if ( is_null( $query ) )
      return;

    $args = func_get_args();
    array_shift( $args );
    // If args were passed as an array (as in vsprintf), move them up
    if ( isset( $args[0] ) && is_array($args[0]) )
      $args = $args[0];
    $query = str_replace( "'%s'", '%s', $query ); // in case someone mistakenly already singlequoted it
    $query = str_replace( '"%s"', '%s', $query ); // doublequote unquoting
    $query = preg_replace( '|(?<!%)%f|' , '%F', $query ); // Force floats to be locale unaware
    $query = preg_replace( '|(?<!%)%s|', "'%s'", $query ); // quote the strings, avoiding escaped strings like %%s
    array_walk( $args, array( $this, 'escape_by_ref' ) );
    return @vsprintf( $query, $args );
  }

  /**
   * Retrieve the name of the function that called wpdb.
   *
   * Searches up the list of functions until it reaches
   * the one that would most logically had called this method.
   *
   * @since 2.5.0
   *
   * @return string The name of the calling function
   */
  function get_caller() {
    return wp_debug_backtrace_summary( __CLASS__ );
  }

  /**
   * Selects a database using the current database connection.
   *
   * The database name will be changed based on the current database
   * connection. On failure, the execution will bail and display an DB error.
   *
   * @since 0.71
   *
   * @param string $db MySQL database name
   * @param resource $dbh Optional link identifier.
   * @return null Always null.
   */
  function select( $db, $dbh = null ) {
    if ( is_null($dbh) )
      $dbh = $this->dbh;

    if ( !@mysql_select_db( $db, $dbh ) ) {
      $this->ready = false;

      $this->bail( sprintf( '<h1>Can&#8217;t select database</h1>
      <p>We were able to connect to the database server (which means your username and password is okay) but not able to select the <code>%1$s</code> database.</p>
      <ul>
      <li>Are you sure it exists?</li>
      <li>Does the user <code>%2$s</code> have permission to use the <code>%1$s</code> database?</li>
      <li>On some systems the name of your database is prefixed with your username, so it would be like <code>username_%1$s</code>. Could that be the problem?</li>
      </ul>
      <p>If you don\'t know how to set up a database you should <strong>contact your host</strong>.</p>', htmlspecialchars( $db, ENT_QUOTES ), htmlspecialchars( $this->dbuser, ENT_QUOTES ) ), 'db_select_fail' );
      return;
    }
  }

  /**
   * Perform a MySQL database query, using current database connection.
   *
   * More information can be found on the codex page.
   *
   * @since 0.71
   *
   * @param string $query Database query
   * @return int|false Number of rows affected/selected or false on error
   */
  function query( $query ) {
    if ( ! $this->ready )
      return false;
    /**
     * Filter the database query.
     *
     * Some queries are made before the plugins have been loaded, and thus cannot be filtered with this method.
     *
     * @since 2.1.0
     * @param string $query Database query.
     */
    $return_val = 0;
    $this->flush();

    // Log how the function was called
    $this->func_call = "\$db->query(\"$query\")";

    // Keep track of the last query for debug..
    $this->last_query = $query;

    $this->result = @mysql_query( $query, $this->dbh );
    $this->num_queries++;

    // If there is an error then take note of it..
    if ( $this->last_error = mysql_error( $this->dbh ) ) {
      // Clear insert_id on a subsequent failed insert.
      if ( $this->insert_id && preg_match( '/^\s*(insert|replace)\s/i', $query ) )
        $this->insert_id = 0;

      $this->print_error();
      return false;
    }

    if ( preg_match( '/^\s*(create|alter|truncate|drop)\s/i', $query ) ) {
      $return_val = $this->result;
    } elseif ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) ) {
      $this->rows_affected = mysql_affected_rows( $this->dbh );
      // Take note of the insert_id
      if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
        $this->insert_id = mysql_insert_id($this->dbh);
      }
      // Return number of rows affected
      $return_val = $this->rows_affected;
    } else {
      $num_rows = 0;
      while ( $row = @mysql_fetch_object( $this->result ) ) {
        $this->last_result[$num_rows] = $row;
        $num_rows++;
      }

      // Log number of rows the query returned
      // and return number of rows selected
      $this->num_rows = $num_rows;
      $return_val     = $num_rows;
    }

    return $return_val;
  }

  /**
   * Retrieve an entire SQL result set from the database (i.e., many rows)
   *
   * Executes a SQL query and returns the entire SQL result.
   *
   * @since 0.71
   *
   * @param string $query SQL query.
   * @param string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants. With one of the first three, return an array of rows indexed from 0 by SQL result row number.
   *  Each row is an associative array (column => value, ...), a numerically indexed array (0 => value, ...), or an object. ( ->column = value ), respectively.
   *  With OBJECT_K, return an associative array of row objects keyed by the value of each row's first column's value. Duplicate keys are discarded.
   * @return mixed Database query results
   */
  function get_results( $query = null, $output = OBJECT ) {
    $this->func_call = "\$db->get_results(\"$query\", $output)";

    if ( $query )
      $this->query( $query );
    else
      return null;

    $new_array = array();
    if ( $output == OBJECT ) {
      // Return an integer-keyed array of row objects
      return $this->last_result;
    } elseif ( $output == OBJECT_K ) {
      // Return an array of row objects with keys from column 1
      // (Duplicates are discarded)
      foreach ( $this->last_result as $row ) {
        $var_by_ref = get_object_vars( $row );
        $key = array_shift( $var_by_ref );
        if ( ! isset( $new_array[ $key ] ) )
          $new_array[ $key ] = $row;
      }
      return $new_array;
    } elseif ( $output == ARRAY_A || $output == ARRAY_N ) {
      // Return an integer-keyed array of...
      if ( $this->last_result ) {
        foreach( (array) $this->last_result as $row ) {
          if ( $output == ARRAY_N ) {
            // ...integer-keyed row arrays
            $new_array[] = array_values( get_object_vars( $row ) );
          } else {
            // ...column name-keyed row arrays
            $new_array[] = get_object_vars( $row );
          }
        }
      }
      return $new_array;
    }
    return null;
  }

  /**
   * Retrieve one variable from the database.
   *
   * Executes a SQL query and returns the value from the SQL result.
   * If the SQL result contains more than one column and/or more than one row, this function returns the value in the column and row specified.
   * If $query is null, this function returns the value in the specified column and row from the previous SQL result.
   *
   * @since 0.71
   *
   * @param string|null $query Optional. SQL query. Defaults to null, use the result from the previous query.
   * @param int $x Optional. Column of value to return. Indexed from 0.
   * @param int $y Optional. Row of value to return. Indexed from 0.
   * @return string|null Database query result (as string), or null on failure
   */
  function get_var( $query = null, $x = 0, $y = 0 ) {
    $this->func_call = "\$db->get_var(\"$query\", $x, $y)";
    if ( $query )
      $this->query( $query );

    // Extract var out of cached results based x,y vals
    if ( !empty( $this->last_result[$y] ) ) {
      $values = array_values( get_object_vars( $this->last_result[$y] ) );
    }

    // If there is a value return it else return null
    return ( isset( $values[$x] ) && $values[$x] !== '' ) ? $values[$x] : null;
  }

  /**
   * Retrieve one row from the database.
   *
   * Executes a SQL query and returns the row from the SQL result.
   *
   * @since 0.71
   *
   * @param string|null $query SQL query.
   * @param string $output Optional. one of ARRAY_A | ARRAY_N | OBJECT constants. Return an associative array (column => value, ...),
   *  a numerically indexed array (0 => value, ...) or an object ( ->column = value ), respectively.
   * @param int $y Optional. Row to return. Indexed from 0.
   * @return mixed Database query result in format specified by $output or null on failure
   */
  function get_row( $query = null, $output = OBJECT, $y = 0 ) {
    $this->func_call = "\$db->get_row(\"$query\",$output,$y)";
    if ( $query )
      $this->query( $query );
    else
      return null;

    if ( !isset( $this->last_result[$y] ) )
      return null;

    if ( $output == OBJECT ) {
      return $this->last_result[$y] ? $this->last_result[$y] : null;
    } elseif ( $output == ARRAY_A ) {
      return $this->last_result[$y] ? get_object_vars( $this->last_result[$y] ) : null;
    } elseif ( $output == ARRAY_N ) {
      return $this->last_result[$y] ? array_values( get_object_vars( $this->last_result[$y] ) ) : null;
    } else {
      $this->print_error( " \$db->get_row(string query, output type, int offset) -- Output type must be one of: OBJECT, ARRAY_A, ARRAY_N" );
    }
  }

  /**
   * Retrieve one column from the database.
   *
   * Executes a SQL query and returns the column from the SQL result.
   * If the SQL result contains more than one column, this function returns the column specified.
   * If $query is null, this function returns the specified column from the previous SQL result.
   *
   * @since 0.71
   *
   * @param string|null $query Optional. SQL query. Defaults to previous query.
   * @param int $x Optional. Column to return. Indexed from 0.
   * @return array Database query result. Array indexed from 0 by SQL result row number.
   */
  function get_col( $query = null , $x = 0 ) {
    if ( $query )
      $this->query( $query );

    $new_array = array();
    // Extract the column values
    for ( $i = 0, $j = count( $this->last_result ); $i < $j; $i++ ) {
      $new_array[$i] = $this->get_var( null, $x, $i );
    }
    return $new_array;
  }

  /**
   * Kill cached query results.
   *
   * @since 0.71
   * @return void
   */
  function flush() {
    $this->last_result = array();
    $this->col_info    = null;
    $this->last_query  = null;
    $this->rows_affected = $this->num_rows = 0;
    $this->last_error  = '';

    if ( is_resource( $this->result ) )
      mysql_free_result( $this->result );
  }

}