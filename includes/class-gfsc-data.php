<?php
/**
 * Entry aggregation.
 *
 * @package SubmissionsChartForGF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFSC_Data {

	/**
	 * Counts active entries per local calendar day.
	 *
	 * Gravity Forms stores date_created in UTC, so the range bounds are converted
	 * to UTC for the query and each timestamp is shifted back to the site timezone
	 * before bucketing. Only the date_created column is read, so this stays cheap
	 * even for large forms.
	 *
	 * @param int         $form_id Form ID, or 0 for all forms.
	 * @param string|null $start   Local start date (Y-m-d), inclusive.
	 * @param string|null $end     Local end date (Y-m-d), inclusive.
	 * @return array{labels: string[], counts: int[], total: int}
	 */
	public static function submissions_by_day( $form_id, $start = null, $end = null ) {
		global $wpdb;

		$site_tz = wp_timezone();
		$utc_tz  = new DateTimeZone( 'UTC' );
		$table   = GFFormsModel::get_entry_table_name();
		$where   = array( "status = 'active'" );
		$args    = array();

		if ( $form_id ) {
			$where[] = 'form_id = %d';
			$args[]  = $form_id;
		} else {
			// Ignore entries belonging to trashed forms.
			$where[] = 'form_id IN (' . implode( ',', array_map( 'absint', self::active_form_ids() ) ?: array( 0 ) ) . ')';
		}

		if ( $start ) {
			$where[] = 'date_created >= %s';
			$args[]  = ( new DateTime( $start . ' 00:00:00', $site_tz ) )->setTimezone( $utc_tz )->format( 'Y-m-d H:i:s' );
		}
		if ( $end ) {
			$where[] = 'date_created <= %s';
			$args[]  = ( new DateTime( $end . ' 23:59:59', $site_tz ) )->setTimezone( $utc_tz )->format( 'Y-m-d H:i:s' );
		}

		$sql = "SELECT date_created FROM {$table} WHERE " . implode( ' AND ', $where );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_col( $args ? $wpdb->prepare( $sql, $args ) : $sql );

		$map = array();
		foreach ( $rows as $created ) {
			$day         = ( new DateTime( $created, $utc_tz ) )->setTimezone( $site_tz )->format( 'Y-m-d' );
			$map[ $day ] = ( $map[ $day ] ?? 0 ) + 1;
		}

		if ( ! $map && ! ( $start && $end ) ) {
			return array(
				'labels' => array(),
				'counts' => array(),
				'total'  => 0,
			);
		}

		ksort( $map );
		$keys  = array_keys( $map );
		$first = $start ? $start : reset( $keys );
		$last  = $end ? $end : end( $keys );

		$labels = array();
		$counts = array();
		$cursor = new DateTime( $first, $site_tz );
		$finish = new DateTime( $last, $site_tz );

		while ( $cursor <= $finish ) {
			$key      = $cursor->format( 'Y-m-d' );
			$labels[] = $key;
			$counts[] = $map[ $key ] ?? 0;
			$cursor->modify( '+1 day' );
		}

		return array(
			'labels' => $labels,
			'counts' => $counts,
			'total'  => array_sum( $counts ),
		);
	}

	private static function active_form_ids() {
		return wp_list_pluck( GFAPI::get_forms( true, false ), 'id' );
	}
}
