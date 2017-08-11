<?php
/**
 * Plugin Name: GP Live Preview
 * Description: Preview your forms on the frontend of your site.
 * Plugin URI: http://gravitywiz.com/documentation/gp-live-preview/
 * Version: 1.2.7
 * Author: Gravity Wiz
 * Author URI: http://gravitywiz.com/
 * License: GPL2
 * Perk: True
 * Text Domain: gp-live-preview
 * Domain Path: /languages
 */

define( 'GP_LIVE_PREVIEW_VERSION', '1.2.7' );

require 'includes/class-gp-bootstrap.php';

$gp_live_preview_bootstrap = new GP_Bootstrap( 'class-gp-live-preview.php', __FILE__ );