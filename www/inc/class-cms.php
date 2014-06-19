<?php

if (!class_exists(db)):
  include 'class-db.php';
endif;

if (!class_exists(api)):
  include 'class-api.php';
endif;


class cms {
  private $dir = 'template';
  
  public $api;

  private $page = array();

  public $query_vars = array();

  function __construct($default) {
    $template = $this->template_match($default);

    if ($template):
      $this->template = $template;
    else:
      return false;
    endif;

    $db = new db(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
    $this->api = new api($_GET, $db);
    $this->query_vars = $_GET;
  }

  private function template_list() {
    $handle = opendir(ABSPATH . '/' . $this->dir );

    if ($handle):
      $out = array();

      while (($file = readdir($handle)) !== false):
        if (substr($file, -3) == 'php'):
          $out[] = substr($file, 0, -4);
        endif;
      endwhile;

      closedir($handle);      
    endif;
    
    return $out;
  }

  private function template_match($default_template) {
    $templates = $this->template_list();

    if ($_SERVER['REQUEST_URI'] == '/' . BASE_DIR . '/')
      return $default_template;

    foreach ($templates as $k => $template)
      if (preg_match(','. $template .',', $_SERVER['REQUEST_URI']))
        return $template;
    return false;
  }

  public function display() {
    $path = array($this->dir, $this->template . '.php');

    return join('/', $path);
  }

  function get_header() {
    return $this->dir . '/includes/header.php';
  }

  function get_footer() {
    return $this->dir . '/includes/footer.php';
  }

  function is_page_template($arg) {
  	return $arg == $this->template;
  }

  function page_add($key, $val) {
    $this->page[$key] = $val;
  }

  function current_page() {
    return $this->template;
  }

  function home_url($return=true) {
    $result = '//' . SITE_URL . '/' . BASE_DIR;
    if ($return):
      return $result;
    else:
      echo $result;
    endif;
  }

  function absolute_url($path, $return=true) {
    $result = $this->home_url() . $path ;
    if ($return):
      return $result;
    else:
      echo $result;
    endif;
  }

  public function page($key) {
    if (array_key_exists($key, $this->page)):
      return $this->page[$key];
    endif;
  }
}