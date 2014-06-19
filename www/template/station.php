<?php
/**
 * @package bikeshare-dashboard
*/
if(isset($cms->query_vars['station'])) {
    $station_id = $cms->query_vars['station'];
    // Get the station name. Station Info is called with XHR request by JS.
    $station = $cms->api->get_station_meta($station_id);

    $since = $cms->api->get_query_var('since', 1);
    $starttime = $cms->api->get_query_var('starttime', NULL);
    $endtime = $cms->api->get_query_var('endtime', NULL);
}

$cms->page_add('stationName', (isset($station->stationName)) ? $station->stationName : "Couldn't find that station");

$activity_url = $cms->absolute_url(sprintf("/api/station_activity/%d/?starttime=%s&endtime=%s", $station_id, $starttime, $endtime));
$pattern_url = $cms->absolute_url("/api/station_pattern/". $station_id .'/');
$trips_url = $cms->absolute_url("/api/station_trips/". $station_id .'/?starttime='. $starttime .'&endtime='. $endtime);
include $cms->get_header();
?>

<h2><?php echo $cms->page('stationName'); ?></h2>

<p>Showing the last <?php echo pluralize($since); ?>.</p>

<form action="./" class="form-inline">
  <input type="hidden" name="station" value="<?php echo $station_id ?>">
  <p>
    Show me the last <input type="text" class="input-mini" name="since" id="since"> hours <button type="submit" class="btn btn-info">Go</button>
  </p>
</form>

<form action="./" class="form-inline">
  <input type="hidden" name="station" value="<?php echo $station_id ?>">
  <p>
    Show me from <input type="date" class="input-medium" name="starttime" id="starttime"> to <input type="date" class="input-medium" name="endtime" id="endtime"> <button type="submit" class="btn btn-info">Go</button>
  </p>
</form>

<p>There are <?php echo $station->totalDocks; ?> docks here.</p>
<div id="activity" class="d3-graph"></div>

<p><a href="../map/?station=<?php echo $station_id ?>">View on map</a></p>
<p>"Null" docks aren't shown as either being filled with bikes or available.</p>

<p><a href="../station_activity_csv/<?php echo $station_id . '/?since=' . $since; ?>">Download data (csv)</a></p>

<hr>

<p>Average for recent weeks.</p>

<div id="pattern" class="d3-graph"></div>

<p><a href="../bikeshare-dashboard/">⃪ System Dashboard</a></p>

<script>
  var since = <?php echo $since; ?>,
      starttime = '<?php echo $starttime; ?>',
      endtime = '<?php echo $endtime; ?>',
      station_id = <?php echo $station_id; ?>,
      STATION_ACTIVITY_URL = '<?php echo $activity_url ?>',
      STATION_PATTERN_URL = '<?php echo $pattern_url; ?>',
      STATION_TRIPS_URL = '<?php echo $trips_url; ?>';
</script>
<script src="<?php $cms->absolute_url('/assets/js/dashboard-station.js', 0) ?>"></script>

<?php include $cms->get_footer(); ?>
