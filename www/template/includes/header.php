<?php
/**
 * @package bikeshare-dashboard
 */
    ?><!DOCTYPE html>
<!--[if lt IE 7 ]> <html class="ie6"> <![endif]-->
<!--[if IE 7 ]>    <html class="ie7"> <![endif]-->
<!--[if IE 8 ]>    <html class="ie8"> <![endif]-->
<!--[if IE 9 ]>    <html class="ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--> <html> <!--<![endif]-->

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="chrome=1">

    <title><?php
        /*
         * Print the <title> tag based on what is being viewed.
         */
        if ($cms->is_page_template('dashboard-station')):
          echo $cms->page('stationName') . ' | ';
        else:
          // noop
        endif;
          echo 'bikeshare dashboard';

    ?></title>
    <!--  Mobile Viewport Fix -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="profile" href="http://gmpg.org/xfn/11" />

    <script type="text/javascript" src="<?php $cms->absolute_url("/assets/js/d3.v3.min.js", 0) ?>"></script>
    <link rel="stylesheet" media="screen" href="<?php $cms->absolute_url("/assets/css/style.css", 0) ?>">

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
        /* d3 charts */
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
        .circle-end {
          fill: rgba(240, 25, 25, 0.33);
        }
        .circle-start {
          fill: rgba(25, 25, 240, 0.33);
        }
    </style>

</head>

  <body>
    <div class="container">
