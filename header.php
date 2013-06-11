<?php
/**
 * @package WordPress
 * @subpackage bikeshare
 */
    ?><!DOCTYPE html>
<!--[if lt IE 7 ]> <html <?php language_attributes(); ?> class="ie6"> <![endif]-->
<!--[if IE 7 ]>    <html <?php language_attributes(); ?> class="ie7"> <![endif]-->
<!--[if IE 8 ]>    <html <?php language_attributes(); ?> class="ie8"> <![endif]-->
<!--[if IE 9 ]>    <html <?php language_attributes(); ?> class="ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html <?php language_attributes(); ?>> <!--<![endif]-->

<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <meta http-equiv="X-UA-Compatible" content="chrome=1">

    <title><?php
        /*
         * Print the <title> tag based on what is being viewed.
         */
        global $page, $paged;

        if (is_page_template('dashboard-station.php')):
          echo $post->stationName . ' | ';
        endif;

        wp_title( '|', true, 'right' );

        // Add the blog name.
        bloginfo( 'name' );

    ?></title>
    <!--  Mobile Viewport Fix -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Place favicon.ico and apple-touch-icon.png in the images folder -->
    <link rel="shortcut icon" href="<?php echo get_template_directory_uri(); ?>/images/favicon.ico">
    <link rel="apple-touch-icon" href="<?php echo get_template_directory_uri(); ?>/images/apple-touch-icon.png"><!--60X60-->

    <link rel="profile" href="http://gmpg.org/xfn/11" />
    <script type="text/javascript" src="<?php echo get_stylesheet_directory_uri(); ?>/js/d3.v3.min.js"></script>
    <link rel="stylesheet" media="screen" href="<?php echo get_stylesheet_directory_uri(); ?>/style.css"></link>

  <!--[if lt IE 9]>
    <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->

    <style>
        .cols-three {
          margin-left: 0;
          columns:100px 3;
          -webkit-columns:100px 3; /* Safari and Chrome */
          -moz-columns:100px 3; /* Firefox */
        }
        .d3-graph {
          font-size: 10px;
        }
        .container {
          padding-top: 1em;
        }
        .axis path,
        .axis line {
          fill: none;
          stroke: #000;
          shape-rendering: crispEdges;
        }
        .x.axis path {
          display: none;
        }
        .bike path {
          fill: none;
          stroke-width: 3px;
        }
        .line-steelblue {
          fill: none;
          stroke: steelblue;
          stroke-width: 3px;
        }
        #legend { 
          position: absolute;
          left: 10px;
          height: 240px;
          width: 100px;
        }
        .legend text {
          font: 10px sans-serif;
        }
        .legend .legtitle {
          font: 12px sans-serif;
        }
        .legend circle {
          stroke-width: 1.25px;
          fill: transparent;
        }
        .legend rect.full {
          stroke-width: 0.88px;
        }
        /* fix for bootstrap/gmap style conflict */
        .gmap img { max-width: none; }

        .infowindow {
          line-height: 1.1em;
        }

    </style>

  <?php wp_head(); ?>

</head>

  <body <?php body_class(); ?>>
    <div class="container">
