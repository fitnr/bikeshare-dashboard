<?php
/*
  * Front end functions
  * These are used by the template pages
*/

include 'class-api.php';
include 'class-cms.php';

date_default_timezone_set(TIMEZONE);

/*
  * Compare stations based on the diff docks
  * (The difference between the max and min number of  docks available over a time period)
  * Used with usort
*/
function diffockcmp($a, $b) { return $a->diffDocks > $b->diffDocks; }

/*
  * Used for filtering by different station station keys.
*/
function status1($x) { return ($x->status == '1'); }
function status2($x) { return ($x->status == '2'); }
function status3($x) { return ($x->status == '3'); }

/**
  * return either the singular or plural of a number
*/
function pluralize($x, $singular='hour', $plural='hours') {
    return ($x == 1) ? $singular : $x . ' '.$plural;
}

/**
 * Echo a list items for stations. Give special formatting to those with diffDocks=0
 * @param array $data An array of objects. Each object describes a stations, with an id, name, and dock information
 * @param string $line Formatting string for arranging the link, stationId, stationName, maxDocks and minDocks
 * @param string $link the link to info about each station, will be appended to the bloginfo->home.
 * 
 * @return string A html list.
 *
*/
function station_list($data, $line='%s (%s, %s)', $link='station/?station=') {
    $output = '';
    foreach ($data as $value):
        if ($value->diffDocks > 0):
            $format = $line;
        else:
            $format = '<span class="badge badge-important">' . $line . '</span>';
        endif;

        $format = '<a href="%s'. $link .'%s">'. $format .'</a>';

        $output .= "  <li>". sprintf($format, '', $value->id, $value->stationName, $value->maxDocks, $value->minDocks) ."</li>\n";

    endforeach;
    return $output;
}
