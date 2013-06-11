<?php
/**
 * Template Name: Bikeshare Dashboard Map
 * @package WordPress
 * @subpackage bikeshare-dashboard
*/
get_header();
?>
<style>
  /* gmap rules */
  html, body, .container { height: 100%; }
  .map-container { height: 95%;}
  #the-map { width:100%; height: 100%;}
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
<script src="<?php echo get_stylesheet_directory_uri(); ?>/js/map-dashboard.js"></script>
<script type="text/javascript" id="globals">
	var endpoint = "<?php echo home_url(); ?>/get_station_locations";
	google.maps.event.addListener(window, 'domready', bikemapinit(endpoint));
</script>

<?php get_footer(); ?>