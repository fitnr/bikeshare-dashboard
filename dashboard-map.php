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
  <div id="the-map" class="gmap"></div>
</div>

<div class="row">
	<p><a href="../bikeshare-dashboard/">⃪ System Dashboard</a></p>
</div>
<!-- Google Map's API -->
<script src="http://maps.google.com/maps/api/js?v=3.5&amp;sensor=false"></script>
<script src="<?php echo get_template_directory_uri(); ?>/js/infobox.min.js"></script>
<script src="<?php echo get_stylesheet_directory_uri(); ?>/js/map-dashboard.js"></script>
<script type="text/javascript" id="globals">
  bikemapinit("<?php echo home_url(); ?>/get_station_locations");
</script>

<?php get_footer(); ?>