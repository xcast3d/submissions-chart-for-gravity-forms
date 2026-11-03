<?php
/**
 * Forms → Submissions Chart page markup.
 *
 * @package SubmissionsChartForGF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap gfsc-page">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Submissions Chart', 'submissions-chart-for-gf' ); ?></h1>
	<hr class="wp-header-end">

	<div id="gfsc-chart-card">
		<div id="gfsc-header">
			<div id="gfsc-title-row">
				<label for="gfsc-form-select" class="screen-reader-text"><?php esc_html_e( 'Form', 'submissions-chart-for-gf' ); ?></label>
				<select id="gfsc-form-select"></select>
				<h2 id="gfsc-title"></h2>
			</div>
			<div id="gfsc-controls">
				<div id="gfsc-range-toggles" role="group" aria-label="<?php esc_attr_e( 'Date range', 'submissions-chart-for-gf' ); ?>">
					<button type="button" class="gfsc-range-btn" data-range="7"><?php esc_html_e( 'Last 7 Days', 'submissions-chart-for-gf' ); ?></button>
					<button type="button" class="gfsc-range-btn active" data-range="30"><?php esc_html_e( 'Last 30 Days', 'submissions-chart-for-gf' ); ?></button>
					<button type="button" class="gfsc-range-btn" data-range="90"><?php esc_html_e( 'Last 90 Days', 'submissions-chart-for-gf' ); ?></button>
					<button type="button" class="gfsc-range-btn" data-range="all"><?php esc_html_e( 'All Time', 'submissions-chart-for-gf' ); ?></button>
					<button type="button" class="gfsc-range-btn" data-range="custom"><?php esc_html_e( 'Custom', 'submissions-chart-for-gf' ); ?></button>
				</div>
				<div id="gfsc-custom-range" hidden>
					<label for="gfsc-start"><?php esc_html_e( 'From', 'submissions-chart-for-gf' ); ?></label>
					<input type="date" id="gfsc-start" />
					<label for="gfsc-end"><?php esc_html_e( 'To', 'submissions-chart-for-gf' ); ?></label>
					<input type="date" id="gfsc-end" />
					<button type="button" id="gfsc-apply-custom" class="button button-primary button-small"><?php esc_html_e( 'Apply', 'submissions-chart-for-gf' ); ?></button>
				</div>
			</div>
		</div>
		<div id="gfsc-chart-area">
			<div id="gfsc-loading">
				<span class="gfsc-spinner"></span>
				<?php esc_html_e( 'Loading chart…', 'submissions-chart-for-gf' ); ?>
			</div>
			<canvas id="gfsc-canvas" aria-label="<?php esc_attr_e( 'Submissions chart', 'submissions-chart-for-gf' ); ?>" role="img"></canvas>
			<div id="gfsc-empty" hidden><?php esc_html_e( 'No submissions found for this period.', 'submissions-chart-for-gf' ); ?></div>
		</div>
	</div>
</div>
