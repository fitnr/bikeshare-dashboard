<?php
/**
 * Template Name: Bikeshare Dashboard Map
 * @package bikeshare-dashboard
*/
include $cms->get_header();
?>
<style>
  /* gmap rules */
  html, body, .container { height: 100%; }
  .map-container { height: 95%; position: relative;}
  #the-map { width:100%; height: 100%;}

  /* legend */
  #legend { 
    position: absolute;
    right: 0;
    height: 231px;
    width: 83px;
    z-index: 10;
    background-color: rgba(256,256,256,.8);
    padding: 10px;
    top: 0;
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
    margin: 0;
  }
  p.infowindow {
    line-height: 1.1em;
  }
</style>

<div class="row map-container">
	<div id="legend">
		<svg>
			<g class="legend">
				<text class="legtitle" x="0" y="11">station fullness</text>
				<circle r="7" cx="8" cy="160" style="stroke: #ff0000;"></circle>
				<circle r="7" cx="8" cy="180" style="stroke: #023858; "></circle>
				<circle r="7" cx="8" cy="200" style="stroke: none; fill: #ff0000; "></circle>
				<circle r="7" cx="8" cy="220" style="stroke: #0033dd; stroke-width: 2.25px;"></circle>
				<text x="20" y="223">planned</text>
				<text x="20" y="163">empty</text>
				<text x="20" y="183">full</text>
				<text x="20" y="203">not in service</text>				
			</g>
		</svg>
	</div>
  <div id="the-map" class="gmap"></div>
</div>

<div class="row">
	<p>Map updates every 2 minutes.</p>
	<hr>
	<p><a href="../bikeshare-dashboard/">⃪ System Dashboard</a></p>
</div>
<!-- Google Map's API -->
<script src="http://maps.google.com/maps/api/js?v=3.5&amp;sensor=false"></script>
<script src="<?php $cms->absolute_url('/assets/js/map-dashboard.js', 0) ?>"></script>
<script type="text/javascript" id="globals">
	var endpoint = "<?php $cms->absolute_url('/api/get_station_locations/', 0)?>";
	google.maps.event.addListener(window, 'domready', bikemapinit(endpoint));
</script>

<?php include $cms->get_footer(); ?>