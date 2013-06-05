<?php
/**
 * @package WordPress
 * @subpackage bikeshare-dashboard
 */

// Wordpress drops you here if a custom query var is included with a designated front page.
// Doesn't make much sense, but the solution is to just include the desired template
if (isset($wp_query->query_vars['since'])):
	get_template_part('dashboard', 'bikeshare');
else:
	// Otherwise, just give an empty page.
	get_header('dashboard');
	get_footer('dashboard');
endif;
?>