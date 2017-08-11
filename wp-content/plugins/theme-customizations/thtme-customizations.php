<?php
/*
Plugin Name: Theme Customizations
Plugin URI: None
Description: Dump code here instead of functions.php
Author: Copy and Paste By Marc Gratch
Version: 1.0
*/

add_action( 'presenter-head', 'wp_head' );



/* adds stylesheet file to the end of the queue */
function tweak_reveal_css()
{
	$dir = plugin_dir_url(__FILE__);
	wp_enqueue_style('tweak-reveal-css', $dir . '/theme-overrides.css', array(), '0.1.0', 'all');

	if (in_category('legacy')){
		wp_enqueue_script('tweak-reveal-js', $dir . '/theme-overrides.js', array('jquery'), '0.1.0', false);
	}
}
add_action('wp_enqueue_scripts', 'tweak_reveal_css');

add_action('init','add_categories_to_cpt', 99);
function add_categories_to_cpt(){
	register_taxonomy_for_object_type('category', 'slideshow');
}