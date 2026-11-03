<?php
/**
 * Plugin Name:       Submission Chart Add-on for Gravity Forms
 * Description:       Adds a Forms → Submissions Chart page that graphs Gravity Forms entries by day.
 * Version:           2.0.0-dev
 * Author:            Xavier Castañeda
 * Author URI:        https://xcast3d.com
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Requires Plugins:  gravityforms
 * Text Domain:       submissions-chart-for-gf
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GFSC_VERSION', '2.0.0-dev' );
define( 'GFSC_PLUGIN_FILE', __FILE__ );
define( 'GFSC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GFSC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once GFSC_PLUGIN_DIR . 'includes/class-gfsc-data.php';
require_once GFSC_PLUGIN_DIR . 'includes/class-gfsc-admin.php';

add_action( 'gform_loaded', array( 'GFSC_Admin', 'init' ) );
