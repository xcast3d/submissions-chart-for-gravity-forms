<?php
/**
 * Admin page, assets and AJAX endpoint.
 *
 * @package SubmissionsChartForGF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFSC_Admin {

	const PAGE_SLUG  = 'gfsc_submissions_chart';
	const CAPABILITY = 'gravityforms_view_entries';
	const NONCE      = 'gfsc_get_data';

	public static function init() {
		add_filter( 'gform_addon_navigation', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_gfsc_get_data', array( __CLASS__, 'ajax_get_data' ) );
		add_action( 'wp_ajax_gfsc_get_day', array( __CLASS__, 'ajax_get_day' ) );
		add_action( 'wp_ajax_gfsc_update_entry', array( __CLASS__, 'ajax_update_entry' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * GF scopes much of its admin styling to body.gform-admin.
	 */
	public static function body_class( $classes ) {
		return self::is_chart_page() ? $classes . ' gform-admin gform-admin-screen' : $classes;
	}

	/**
	 * Adds "Submissions Chart" under the Gravity Forms "Forms" menu.
	 */
	public static function register_menu( $menus ) {
		$menus[] = array(
			'name'       => self::PAGE_SLUG,
			'label'      => __( 'Submissions Chart', 'submissions-chart-for-gf' ),
			'callback'   => array( __CLASS__, 'render_page' ),
			'permission' => self::CAPABILITY,
		);
		return $menus;
	}

	private static function is_chart_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing check.
		return is_admin() && isset( $_GET['page'] ) && self::PAGE_SLUG === $_GET['page'];
	}

	/**
	 * Version string that changes with the file, so browsers never pair new page
	 * markup with a cached copy of the old script or stylesheet.
	 */
	private static function asset_version( $path ) {
		return GFSC_VERSION . '.' . filemtime( GFSC_PLUGIN_DIR . $path );
	}

	public static function enqueue_assets() {
		if ( ! self::is_chart_page() ) {
			return;
		}

		// Reuse Gravity Forms' admin + settings styles for its header and centered page wrapper.
		wp_enqueue_style( 'gfsc-admin', GFSC_PLUGIN_URL . 'assets/css/admin.css', array( 'gform_admin', 'gform_settings' ), self::asset_version( 'assets/css/admin.css' ) );
		wp_enqueue_script( 'gfsc-chartjs', GFSC_PLUGIN_URL . 'assets/vendor/chartjs/chart.umd.min.js', array(), '4.4.3', true );
		wp_enqueue_script( 'gfsc-admin', GFSC_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'gfsc-chartjs' ), self::asset_version( 'assets/js/admin.js' ), true );

		$forms = array_map(
			function ( $form ) {
				return array(
					'id'    => absint( $form['id'] ),
					'title' => $form['title'],
				);
			},
			GFAPI::get_forms( true, false, 'title', 'ASC' )
		);

		wp_localize_script(
			'gfsc-admin',
			'GFSC',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'adminUrl' => admin_url( 'admin.php' ),
				'nonce'    => wp_create_nonce( self::NONCE ),
				'forms'    => array_values( $forms ),
				'i18n'     => array(
					'allForms'    => __( 'All forms', 'submissions-chart-for-gf' ),
					'loadError'   => __( 'Failed to load chart data.', 'submissions-chart-for-gf' ),
					'networkErr'  => __( 'Network error loading chart.', 'submissions-chart-for-gf' ),
					'pickDates'   => __( 'Please select both a start and end date.', 'submissions-chart-for-gf' ),
					'badRange'    => __( 'Start date must be on or before end date.', 'submissions-chart-for-gf' ),
					'submission'  => __( 'submission', 'submissions-chart-for-gf' ),
					'submissions' => __( 'submissions', 'submissions-chart-for-gf' ),
					'dayTitle'    => __( 'Submissions on %s', 'submissions-chart-for-gf' ),
					'openEntries' => __( 'Open %s in Entries', 'submissions-chart-for-gf' ),
					'truncated'   => __( 'Showing the latest %1$d of %2$d. Use the Entries links to see the rest.', 'submissions-chart-for-gf' ),
					'view'        => __( 'View', 'submissions-chart-for-gf' ),
					'total'       => __( 'Total', 'submissions-chart-for-gf' ),
					'otherForms'  => __( 'Other forms', 'submissions-chart-for-gf' ),
					'movedSpam'   => __( 'Entry marked as spam.', 'submissions-chart-for-gf' ),
					'movedTrash'  => __( 'Entry moved to the trash.', 'submissions-chart-for-gf' ),
					'undo'        => __( 'Undo', 'submissions-chart-for-gf' ),
					'updateError' => __( 'Could not update the entry.', 'submissions-chart-for-gf' ),
					/* translators: %d: number of selected forms. */
					'formsCount'  => __( '%d forms', 'submissions-chart-for-gf' ),
					'noForms'     => __( 'No forms', 'submissions-chart-for-gf' ),
					'pickForm'    => __( 'Select at least one form.', 'submissions-chart-for-gf' ),
					'date'        => __( 'Date', 'submissions-chart-for-gf' ),
					'filePrefix'  => __( 'submissions', 'submissions-chart-for-gf' ),
				),
			)
		);
	}

	public static function render_page() {
		if ( ! GFCommon::current_user_can_any( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'submissions-chart-for-gf' ) );
		}
		include GFSC_PLUGIN_DIR . 'includes/views/page.php';
	}

	public static function ajax_get_data() {
		check_ajax_referer( self::NONCE, 'nonce' );

		if ( ! GFCommon::current_user_can_any( self::CAPABILITY ) ) {
			wp_send_json_error( __( 'Unauthorized', 'submissions-chart-for-gf' ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
		$form_ids = self::posted_form_ids();
		$range   = isset( $_POST['range'] ) ? sanitize_key( wp_unslash( $_POST['range'] ) ) : 'all';
		$start   = null;
		$end     = null;

		if ( 'custom' === $range ) {
			$start = isset( $_POST['start_date'] ) ? self::sanitize_date( wp_unslash( $_POST['start_date'] ) ) : null;
			$end   = isset( $_POST['end_date'] ) ? self::sanitize_date( wp_unslash( $_POST['end_date'] ) ) : null;
			if ( ! $start || ! $end || $start > $end ) {
				wp_send_json_error( __( 'Invalid date range.', 'submissions-chart-for-gf' ) );
			}
		} elseif ( in_array( $range, array( '7', '30', '90' ), true ) ) {
			$today = new DateTimeImmutable( 'today', wp_timezone() );
			$end   = $today->format( 'Y-m-d' );
			$start = $today->modify( '-' . ( (int) $range - 1 ) . ' days' )->format( 'Y-m-d' );
		}
		// phpcs:enable

		wp_send_json_success( GFSC_Data::submissions_by_day( $form_ids, $start, $end ) );
	}

	/**
	 * Entries for a single local day, across all forms or one form.
	 */
	public static function ajax_get_day() {
		check_ajax_referer( self::NONCE, 'nonce' );

		if ( ! GFCommon::current_user_can_any( self::CAPABILITY ) ) {
			wp_send_json_error( __( 'Unauthorized', 'submissions-chart-for-gf' ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
		$form_ids = self::posted_form_ids();
		$date    = isset( $_POST['date'] ) ? self::sanitize_date( wp_unslash( $_POST['date'] ) ) : null;
		// phpcs:enable

		if ( ! $date ) {
			wp_send_json_error( __( 'Invalid date.', 'submissions-chart-for-gf' ) );
		}

		wp_send_json_success( GFSC_Data::entries_for_day( $form_ids, $date ) );
	}

	/**
	 * Moves an entry to spam or trash, or back to active for Undo, then returns
	 * the form's refreshed Recent Submissions card.
	 */
	public static function ajax_update_entry() {
		check_ajax_referer( self::NONCE, 'nonce' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified above.
		$entry_id = isset( $_POST['entry_id'] ) ? absint( $_POST['entry_id'] ) : 0;
		$status   = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		// phpcs:enable

		$entry = GFAPI::get_entry( $entry_id );
		if ( is_wp_error( $entry ) ) {
			wp_send_json_error( __( 'Entry not found.', 'submissions-chart-for-gf' ), 404 );
		}

		// Only active -> spam/trash and spam/trash -> active are valid here.
		$current = $entry['status'];
		$valid   = 'active' === $current ? in_array( $status, array( 'spam', 'trash' ), true ) : ( 'active' === $status && in_array( $current, array( 'spam', 'trash' ), true ) );
		if ( ! $valid ) {
			wp_send_json_error( __( 'That status change is not allowed.', 'submissions-chart-for-gf' ), 400 );
		}

		// Trash needs delete rights and spam needs edit rights, in both directions, as on GF's Entries list.
		$capability = 'trash' === $status || 'trash' === $current ? 'gravityforms_delete_entries' : 'gravityforms_edit_entries';
		if ( ! GFCommon::current_user_can_any( $capability ) ) {
			wp_send_json_error( __( 'Unauthorized', 'submissions-chart-for-gf' ), 403 );
		}

		// Goes through GF's own update, so Akismet feedback and gform_update_status still fire.
		GFAPI::update_entry_property( $entry_id, 'status', $status );

		$gfsc_form = GFAPI::get_form( $entry['form_id'] );
		ob_start();
		include GFSC_PLUGIN_DIR . 'includes/views/recent-card.php';

		wp_send_json_success( array( 'card' => ob_get_clean() ) );
	}

	/**
	 * The form_ids[] request value as integers. Empty means all forms. Callers verify the nonce.
	 *
	 * @return int[]
	 */
	private static function posted_form_ids() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by absint below.
		$ids = isset( $_POST['form_ids'] ) ? (array) wp_unslash( $_POST['form_ids'] ) : array();
		return array_values( array_filter( array_map( 'absint', $ids ) ) );
	}

	/**
	 * Returns a Y-m-d string or null if the input is not a valid calendar date.
	 */
	private static function sanitize_date( $value ) {
		$value = sanitize_text_field( $value );
		$dt    = DateTime::createFromFormat( '!Y-m-d', $value );
		return ( $dt && $dt->format( 'Y-m-d' ) === $value ) ? $value : null;
	}
}
