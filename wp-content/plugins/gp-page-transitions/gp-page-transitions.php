<?php
/**
 * Plugin Name: GP Page Transitions
 * Description: Animate your Gravity Forms page transitions and automatically progress to the next page when the current page is complete.
 * Plugin URI: http://gravitywiz.com/documentation/gp-page-transitions-for-gravity-forms/
 * Version: 1.0.beta1.1
 * Author: Gravity Wiz
 * Author URI: http://gravitywiz.com/
 * License: GPL2
 * Perk: True
 * Text Domain: gp-page-transitions
 * Domain Path: /languages
 */

define( 'GP_PAGE_TRANSITIONS_VERSION', '1.0.beta1.1' );

require 'includes/class-gp-bootstrap.php';

$gp_page_transitions_bootstrap = new GP_Bootstrap( 'class-gp-page-transitions.php', __FILE__ );