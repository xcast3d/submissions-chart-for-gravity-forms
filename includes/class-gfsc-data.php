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

	/** Matches the length of SERIES_COLORS in assets/js/admin.js. */
	const MAX_COLOR_SERIES = 8;

	/**
	 * Counts active entries per local calendar day.
	 *
	 * Gravity Forms stores date_created in UTC, so the range bounds are converted
	 * to UTC for the query and each timestamp is shifted back to the site timezone
	 * before bucketing. Only the date_created column is read, so this stays cheap
	 * even for large forms.
	 *
	 * @param int[]       $form_ids Form IDs to include, or empty for all forms.
	 * @param string|null $start   Local start date (Y-m-d), inclusive.
	 * @param string|null $end     Local end date (Y-m-d), inclusive.
	 * When more than one form is included the result also has per-form `series`,
	 * covering only forms with entries in the range.
	 *
	 * @return array{labels: string[], counts: int[], total: int, series: array[]}
	 */
	public static function submissions_by_day( $form_ids = array(), $start = null, $end = null ) {
		global $wpdb;

		$site_tz = wp_timezone();
		$utc_tz  = new DateTimeZone( 'UTC' );
		$table   = GFFormsModel::get_entry_table_name();
		$where   = array( "status = 'active'" );
		$args    = array();

		// Limit to active forms, which also ignores entries belonging to trashed forms.
		$active  = self::active_form_ids();
		$ids     = $form_ids ? array_intersect( $form_ids, $active ) : $active;
		$where[] = 'form_id IN (' . implode( ',', array_map( 'absint', $ids ) ?: array( 0 ) ) . ')';

		if ( $start ) {
			$where[] = 'date_created >= %s';
			$args[]  = ( new DateTime( $start . ' 00:00:00', $site_tz ) )->setTimezone( $utc_tz )->format( 'Y-m-d H:i:s' );
		}
		if ( $end ) {
			$where[] = 'date_created <= %s';
			$args[]  = ( new DateTime( $end . ' 23:59:59', $site_tz ) )->setTimezone( $utc_tz )->format( 'Y-m-d H:i:s' );
		}

		$sql = "SELECT form_id, date_created FROM {$table} WHERE " . implode( ' AND ', $where );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $args ? $wpdb->prepare( $sql, $args ) : $sql );

		$map      = array();
		$form_map = array();
		foreach ( $rows as $row ) {
			$day         = ( new DateTime( $row->date_created, $utc_tz ) )->setTimezone( $site_tz )->format( 'Y-m-d' );
			$map[ $day ] = ( $map[ $day ] ?? 0 ) + 1;

			$form_map[ (int) $row->form_id ][ $day ] = ( $form_map[ (int) $row->form_id ][ $day ] ?? 0 ) + 1;
		}

		if ( ! $map && ! ( $start && $end ) ) {
			return array(
				'labels' => array(),
				'counts' => array(),
				'total'  => 0,
				'series' => array(),
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
			'series' => count( $ids ) > 1 ? self::build_series( $form_map, $labels ) : array(),
		);
	}

	/**
	 * Turns form_id => day => count into chart series with a stable color slot.
	 *
	 * The slot follows the form, not its rank in the current range, so a form keeps
	 * its color when the range changes. The palette has MAX_COLOR_SERIES colors: the
	 * busiest forms get those, in form ID order, and the rest fold into one "other"
	 * series rather than reusing a color.
	 */
	private static function build_series( $form_map, $days ) {
		$slots  = self::color_slots();
		$titles = wp_list_pluck( GFAPI::get_forms( true, false ), 'title', 'id' );
		$series = array();
		$other  = array_fill( 0, count( $days ), 0 );

		foreach ( $form_map as $id => $day_counts ) {
			$counts = array();
			foreach ( $days as $i => $day ) {
				$counts[ $i ] = $day_counts[ $day ] ?? 0;
			}

			if ( isset( $slots[ $id ] ) ) {
				$series[] = array(
					'id'     => $id,
					'title'  => $titles[ $id ] ?? '#' . $id,
					'slot'   => $slots[ $id ],
					'counts' => $counts,
				);
			} else {
				foreach ( $counts as $i => $count ) {
					$other[ $i ] += $count;
				}
			}
		}

		usort(
			$series,
			function ( $a, $b ) {
				return $a['slot'] - $b['slot'];
			}
		);

		if ( array_sum( $other ) ) {
			$series[] = array(
				'id'     => 0,
				'title'  => '',
				'slot'   => null,
				'counts' => $other,
			);
		}

		return $series;
	}

	/**
	 * @return array<int,int> form_id => palette slot for the busiest forms overall.
	 */
	private static function color_slots() {
		global $wpdb;

		$table = GFFormsModel::get_entry_table_name();
		$ids   = implode( ',', array_map( 'absint', self::active_form_ids() ) ?: array( 0 ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$busiest = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT form_id FROM {$table} WHERE status = 'active' AND form_id IN ({$ids}) GROUP BY form_id ORDER BY COUNT(*) DESC, form_id ASC LIMIT %d", self::MAX_COLOR_SERIES ) ) );
		sort( $busiest );

		return array_flip( $busiest );
	}

	/**
	 * Latest active entries for a form, newest first.
	 *
	 * @return array{field_label: string, total: int, entries: array[]}
	 */
	public static function recent_entries( $form, $limit = 5 ) {
		$field   = self::summary_field( $form );
		$total   = 0;
		$entries = GFAPI::get_entries(
			$form['id'],
			array( 'status' => 'active' ),
			array(
				'key'       => 'date_created',
				'direction' => 'DESC',
			),
			array(
				'offset'    => 0,
				'page_size' => $limit,
			),
			$total
		);

		return array(
			'field_label' => $field ? GFCommon::get_label( $field ) : __( 'Entry', 'submissions-chart-for-gf' ),
			'total'       => (int) $total,
			'entries'     => is_wp_error( $entries ) ? array() : array_map(
				function ( $entry ) use ( $form, $field ) {
					return self::format_entry( $form, $field, $entry, 'datetime' );
				},
				$entries
			),
		);
	}

	/**
	 * Active entries created on one local calendar day, newest first.
	 *
	 * @param int[]  $form_ids Form IDs to include, or empty for all forms.
	 * @param string $date    Local date (Y-m-d).
	 * @param int    $limit   Maximum entries returned.
	 */
	public static function entries_for_day( $form_ids, $date, $limit = 100 ) {
		$site_tz = wp_timezone();
		$forms   = array();

		foreach ( GFAPI::get_forms( true, false ) as $form ) {
			if ( ! $form_ids || in_array( (int) $form['id'], $form_ids, true ) ) {
				$forms[ (int) $form['id'] ] = $form;
			}
		}

		$empty = array(
			'date'      => $date,
			'label'     => wp_date( get_option( 'date_format' ), ( new DateTime( $date, $site_tz ) )->getTimestamp() ),
			'total'     => 0,
			'entries'   => array(),
			'forms'     => array(),
			'truncated' => false,
		);
		if ( ! $forms ) {
			return $empty;
		}

		$total   = 0;
		$entries = GFAPI::get_entries(
			array_keys( $forms ),
			// GFAPI treats start_date/end_date as site-local time and converts to UTC itself.
			array(
				'status'     => 'active',
				'start_date' => $date . ' 00:00:00',
				'end_date'   => $date . ' 23:59:59',
			),
			array(
				'key'       => 'date_created',
				'direction' => 'DESC',
			),
			array(
				'offset'    => 0,
				'page_size' => $limit,
			),
			$total
		);

		if ( is_wp_error( $entries ) || ! $entries ) {
			return $empty;
		}

		$rows     = array();
		$per_form = array();
		foreach ( $entries as $entry ) {
			$form   = $forms[ (int) $entry['form_id'] ];
			$rows[] = self::format_entry( $form, self::summary_field( $form ), $entry, 'time' );
		}

		// Per-form counts come from a cheap aggregate so they're right even when $rows is truncated.
		foreach ( self::day_counts_by_form( array_keys( $forms ), $date ) as $id => $count ) {
			$per_form[] = array(
				'id'    => (int) $id,
				'title' => $forms[ (int) $id ]['title'],
				'count' => (int) $count,
				'url'   => self::entries_url( $id, $date ),
			);
		}

		return array_merge(
			$empty,
			array(
				'total'     => (int) $total,
				'entries'   => $rows,
				'forms'     => $per_form,
				'truncated' => $total > count( $rows ),
			)
		);
	}

	/**
	 * Gravity Forms' own Entries list for a form, optionally filtered to one local day.
	 */
	public static function entries_url( $form_id, $date = null ) {
		$args = array(
			'page' => 'gf_entries',
			'id'   => (int) $form_id,
		);
		if ( $date ) {
			$args += array(
				'field_id' => 'date_created',
				'operator' => 'is',
				's'        => $date,
			);
		}
		return admin_url( 'admin.php?' . http_build_query( $args ) );
	}

	/**
	 * Picks the field used to summarize an entry: the first Name field, else the
	 * first field labelled "name", else the first field that stores a value.
	 *
	 * @return GF_Field|null
	 */
	public static function summary_field( $form ) {
		static $cache = array();
		$form_id      = (int) $form['id'];

		if ( array_key_exists( $form_id, $cache ) ) {
			return $cache[ $form_id ];
		}

		$candidates = array_filter(
			(array) $form['fields'],
			function ( $field ) {
				return ! $field->displayOnly && ! in_array( $field->type, array( 'captcha', 'honeypot', 'hidden', 'consent', 'fileupload', 'signature' ), true );
			}
		);

		$picked = null;
		foreach ( $candidates as $field ) {
			if ( 'name' === $field->type ) {
				$picked = $field;
				break;
			}
		}
		if ( ! $picked ) {
			foreach ( $candidates as $field ) {
				if ( false !== stripos( (string) $field->label, 'name' ) ) {
					$picked = $field;
					break;
				}
			}
		}
		if ( ! $picked && $candidates ) {
			$picked = reset( $candidates );
		}

		$cache[ $form_id ] = $picked;
		return $picked;
	}

	/**
	 * @param string $date_style 'datetime' (site date + time) or 'time'.
	 */
	private static function format_entry( $form, $field, $entry, $date_style ) {
		$summary = '';
		if ( $field ) {
			$summary = wp_strip_all_tags( (string) $field->get_value_export( $entry, (string) $field->id, true ) );
			$summary = trim( preg_replace( '/\s+/', ' ', $summary ) );
		}

		$timestamp = strtotime( $entry['date_created'] . ' UTC' );
		$format    = 'time' === $date_style
			? get_option( 'time_format' )
			: get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		return array(
			'id'         => (int) $entry['id'],
			'form_id'    => (int) $form['id'],
			'form_title' => $form['title'],
			'summary'    => '' !== $summary ? wp_html_excerpt( $summary, 60, '…' ) : '',
			'date'       => wp_date( $format, $timestamp ),
			'url'        => admin_url( 'admin.php?page=gf_entries&view=entry&id=' . (int) $form['id'] . '&lid=' . (int) $entry['id'] ),
		);
	}

	/**
	 * @return array<int,int> form_id => active entry count for the local day.
	 */
	private static function day_counts_by_form( $form_ids, $date ) {
		global $wpdb;

		$site_tz = wp_timezone();
		$utc_tz  = new DateTimeZone( 'UTC' );
		$table   = GFFormsModel::get_entry_table_name();
		$ids     = implode( ',', array_map( 'absint', $form_ids ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT form_id, COUNT(*) AS c FROM {$table}
				WHERE status = 'active' AND form_id IN ({$ids}) AND date_created BETWEEN %s AND %s
				GROUP BY form_id ORDER BY c DESC",
				( new DateTime( $date . ' 00:00:00', $site_tz ) )->setTimezone( $utc_tz )->format( 'Y-m-d H:i:s' ),
				( new DateTime( $date . ' 23:59:59', $site_tz ) )->setTimezone( $utc_tz )->format( 'Y-m-d H:i:s' )
			)
		);
		// phpcs:enable

		return wp_list_pluck( $rows, 'c', 'form_id' );
	}

	private static function active_form_ids() {
		return wp_list_pluck( GFAPI::get_forms( true, false ), 'id' );
	}
}
