<?php
/**
 * Forms → Submissions Chart page markup.
 *
 * Mirrors Gravity Forms' own admin layout (logo header + centered
 * .gform-settings__wrapper) so the page feels native.
 *
 * @package SubmissionsChartForGF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gfsc_forms = GFAPI::get_forms( true, false, 'title', 'ASC' );
?>
<div class="wrap gf_browser_chrome gfsc-page">
	<header class="gform-settings-header">
		<div class="gform-settings__wrapper">
			<img src="<?php echo esc_url( GFCommon::get_base_url() . '/images/logos/gravity-logo-white.svg' ); ?>" alt="Gravity Forms" width="266">
		</div>
	</header>

	<div class="gform-settings__wrapper gform-settings__wrapper--full gfsc-content">
		<h1 class="gfsc-page-title"><?php esc_html_e( 'Submissions Chart', 'submissions-chart-for-gf' ); ?></h1>
		<hr class="wp-header-end">

		<div id="gfsc-print-head">
			<p class="gfsc-print-site"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<h1><?php esc_html_e( 'Submissions Report', 'submissions-chart-for-gf' ); ?></h1>
			<p id="gfsc-print-range"></p>
			<p id="gfsc-print-forms"></p>
			<p id="gfsc-print-total"></p>
			<p class="gfsc-print-generated">
				<?php
				/* translators: %s: date the report was generated. */
				echo esc_html( sprintf( __( 'Generated %s', 'submissions-chart-for-gf' ), wp_date( get_option( 'date_format' ) ) ) );
				?>
			</p>
		</div>

		<div id="gfsc-chart-card" class="gfsc-card">
			<div id="gfsc-header">
				<div id="gfsc-title-row">
					<div id="gfsc-form-filter">
						<button type="button" id="gfsc-filter-toggle" aria-expanded="false" aria-controls="gfsc-filter-panel">
							<span class="screen-reader-text"><?php esc_html_e( 'Forms:', 'submissions-chart-for-gf' ); ?></span>
							<span id="gfsc-filter-label"></span>
							<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
						</button>
						<div id="gfsc-filter-panel" role="group" aria-label="<?php esc_attr_e( 'Forms shown in the chart', 'submissions-chart-for-gf' ); ?>" hidden>
							<label class="gfsc-filter-all"><input type="checkbox" id="gfsc-filter-all" /> <?php esc_html_e( 'All forms', 'submissions-chart-for-gf' ); ?></label>
							<div id="gfsc-filter-list"></div>
						</div>
					</div>
					<label for="gfsc-chart-type" class="screen-reader-text"><?php esc_html_e( 'Chart type', 'submissions-chart-for-gf' ); ?></label>
					<select id="gfsc-chart-type">
						<option value="bar"><?php esc_html_e( 'Bars', 'submissions-chart-for-gf' ); ?></option>
						<option value="line"><?php esc_html_e( 'Line', 'submissions-chart-for-gf' ); ?></option>
						<option value="lines-by-form"><?php esc_html_e( 'Lines by form', 'submissions-chart-for-gf' ); ?></option>
						<option value="stacked-by-form"><?php esc_html_e( 'Stacked bars by form', 'submissions-chart-for-gf' ); ?></option>
					</select>
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
			<div class="gfsc-card-foot">
				<p class="gfsc-hint"><?php esc_html_e( 'Click a day in the chart to see its entries.', 'submissions-chart-for-gf' ); ?></p>
				<div class="gfsc-export">
					<button type="button" id="gfsc-download-csv" class="button button-small" disabled><?php esc_html_e( 'Download CSV', 'submissions-chart-for-gf' ); ?></button>
					<button type="button" id="gfsc-print" class="button button-small" disabled><?php esc_html_e( 'Print / PDF', 'submissions-chart-for-gf' ); ?></button>
				</div>
			</div>
			<table id="gfsc-print-totals" class="gfsc-table" hidden>
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Form', 'submissions-chart-for-gf' ); ?></th>
						<th scope="col" class="gfsc-print-num"><?php esc_html_e( 'Submissions', 'submissions-chart-for-gf' ); ?></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>

		<div id="gfsc-day" class="gfsc-card" hidden>
			<div class="gfsc-card-head">
				<h2 id="gfsc-day-title"></h2>
				<button type="button" id="gfsc-day-close" class="button-link">
					<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Close', 'submissions-chart-for-gf' ); ?></span>
				</button>
			</div>
			<ul id="gfsc-day-forms"></ul>
			<p id="gfsc-day-note" class="description" hidden></p>
			<table class="widefat striped gfsc-table">
				<thead>
					<tr>
						<th scope="col" class="gfsc-col-time"><?php esc_html_e( 'Time', 'submissions-chart-for-gf' ); ?></th>
						<th scope="col" class="gfsc-col-form"><?php esc_html_e( 'Form', 'submissions-chart-for-gf' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Submitted by', 'submissions-chart-for-gf' ); ?></th>
						<th scope="col" class="gfsc-col-action"><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'submissions-chart-for-gf' ); ?></span></th>
					</tr>
				</thead>
				<tbody id="gfsc-day-rows"></tbody>
			</table>
		</div>

		<?php if ( $gfsc_forms ) : ?>
			<h2 class="gfsc-section-title"><?php esc_html_e( 'Recent Submissions', 'submissions-chart-for-gf' ); ?></h2>
			<div id="gfsc-recent">
				<?php
				foreach ( $gfsc_forms as $gfsc_form ) {
					include GFSC_PLUGIN_DIR . 'includes/views/recent-card.php';
				}
				?>
			</div>
		<?php endif; ?>
	</div>
</div>
