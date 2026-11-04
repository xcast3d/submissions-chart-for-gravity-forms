<?php
/**
 * Plugin Name:       Submission Chart Add-on for Gravity Forms
 * Plugin URI:        https://github.com/xcast3d/submissions-chart-for-gravity-forms
 * Description:       Independent add-on that adds a Forms → Submissions Chart page: entries by day and form, recent entries, CSV download and a printable report.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Xavier Castaneda
 * Author URI:        https://xcast3d.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       submission-chart-add-on-for-gravity-forms
 *
 * @package SubmissionChartAddOnForGravityForms
 */

defined( 'ABSPATH' ) || exit;

define( 'GFSC_VERSION', '1.0.0' );
define( 'GFSC_MIN_GF_VERSION', '2.5' );
define( 'GFSC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GFSC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once GFSC_PLUGIN_DIR . 'includes/class-gfsc-data.php';
require_once GFSC_PLUGIN_DIR . 'includes/class-gfsc-admin.php';

// After Gravity Forms has loaded, so the dependency check can see it.
add_action( 'plugins_loaded', array( 'GFSC_Admin', 'boot' ), 20 );
