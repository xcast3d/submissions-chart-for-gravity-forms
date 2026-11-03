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

		<div id="gfsc-chart-card" class="gfsc-card">
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
			<p class="gfsc-hint"><?php esc_html_e( 'Click a bar to see that day’s entries.', 'submissions-chart-for-gf' ); ?></p>
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
				foreach ( $gfsc_forms as $gfsc_form ) :
					$gfsc_recent = GFSC_Data::recent_entries( $gfsc_form );
					?>
					<section class="gfsc-card gfsc-recent-card" data-form-id="<?php echo absint( $gfsc_form['id'] ); ?>">
						<div class="gfsc-card-head">
							<h3><?php echo esc_html( $gfsc_form['title'] ); ?></h3>
							<a href="<?php echo esc_url( GFSC_Data::entries_url( $gfsc_form['id'] ) ); ?>">
								<?php
								/* translators: %s: total entry count. */
								echo esc_html( sprintf( _n( 'View %s entry', 'View all %s entries', $gfsc_recent['total'], 'submissions-chart-for-gf' ), number_format_i18n( $gfsc_recent['total'] ) ) );
								?>
							</a>
						</div>
						<?php if ( $gfsc_recent['entries'] ) : ?>
							<table class="widefat striped gfsc-table">
								<thead>
									<tr>
										<th scope="col"><?php echo esc_html( $gfsc_recent['field_label'] ); ?></th>
										<th scope="col" class="gfsc-col-date"><?php esc_html_e( 'Submitted', 'submissions-chart-for-gf' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $gfsc_recent['entries'] as $gfsc_entry ) : ?>
										<tr>
											<td>
												<a href="<?php echo esc_url( $gfsc_entry['url'] ); ?>">
													<?php echo '' !== $gfsc_entry['summary'] ? esc_html( $gfsc_entry['summary'] ) : esc_html( sprintf( '#%d', $gfsc_entry['id'] ) ); ?>
												</a>
											</td>
											<td class="gfsc-col-date"><?php echo esc_html( $gfsc_entry['date'] ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php else : ?>
							<p class="gfsc-card-empty"><?php esc_html_e( 'No submissions yet.', 'submissions-chart-for-gf' ); ?></p>
						<?php endif; ?>
					</section>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
