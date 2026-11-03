<?php
/**
 * Seeds sample forms and back-dated entries for local testing.
 *
 * Usage: ./bin/wp.sh eval-file wp-content/plugins/submissions-chart-for-gf/bin/seed.php [days=120]
 *
 * @package SubmissionsChartForGF
 */

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'GFAPI' ) ) {
	exit( "Run via WP-CLI with Gravity Forms active.\n" );
}

$days    = isset( $args[0] ) ? max( 1, (int) $args[0] ) : 120;
$samples = array(
	'Contact Us'         => 6,
	'Newsletter Signup'  => 14,
	'Quote Request'      => 2,
	'<b>Escaped</b> & Co' => 1, // Exercises title escaping in the dropdown.
);

foreach ( $samples as $title => $daily_avg ) {
	$form_id = GFAPI::add_form(
		array(
			'title'  => $title,
			'button' => array(
				'type' => 'text',
				'text' => 'Submit',
			),
			'fields' => array(
				array(
					'id'    => 1,
					'type'  => 'text',
					'label' => 'Name',
				),
				array(
					'id'    => 2,
					'type'  => 'email',
					'label' => 'Email',
				),
			),
		)
	);

	if ( is_wp_error( $form_id ) ) {
		WP_CLI::warning( "{$title}: " . $form_id->get_error_message() );
		continue;
	}

	$entries = array();
	for ( $d = $days - 1; $d >= 0; $d-- ) {
		// Weekday bump plus noise so the chart has some shape.
		$ts    = strtotime( "-{$d} days" );
		$scale = in_array( (int) gmdate( 'N', $ts ), array( 6, 7 ), true ) ? 0.4 : 1.0;
		$count = wp_rand( 0, (int) round( $daily_avg * 2 * $scale ) );

		for ( $i = 0; $i < $count; $i++ ) {
			$n         = count( $entries ) + 1;
			$entries[] = array(
				'form_id'      => $form_id,
				'date_created' => gmdate( 'Y-m-d H:i:s', strtotime( gmdate( 'Y-m-d', $ts ) ) + wp_rand( 0, DAY_IN_SECONDS - 1 ) ),
				'1'            => "Test User {$n}",
				'2'            => "user{$n}@example.test",
			);
		}
	}

	$result = GFAPI::add_entries( $entries, $form_id );
	if ( is_wp_error( $result ) ) {
		WP_CLI::warning( "{$title}: " . $result->get_error_message() );
		continue;
	}

	WP_CLI::log( sprintf( 'Form #%d "%s": %d entries', $form_id, $title, count( $entries ) ) );
}

WP_CLI::success( 'Seeded.' );
