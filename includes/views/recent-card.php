<?php
/**
 * One form's Recent Submissions card. Expects $gfsc_form.
 *
 * Also rendered by the entry status AJAX handler, so a card can be swapped in
 * place after an entry is moved to spam or trash.
 *
 * @package SubmissionChartAddOnForGravityForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$gfsc_recent = GFSC_Data::recent_entries( $gfsc_form );

// Same visibility rules as the row actions on Gravity Forms' Entries list.
$gfsc_can_spam  = GFCommon::spam_enabled( $gfsc_form['id'] ) && GFCommon::current_user_can_any( 'gravityforms_edit_entries' );
$gfsc_can_trash = GFCommon::current_user_can_any( 'gravityforms_delete_entries' );
?>
<section class="gfsc-card gfsc-recent-card" data-form-id="<?php echo absint( $gfsc_form['id'] ); ?>">
	<div class="gfsc-card-head">
		<h3><?php echo esc_html( $gfsc_form['title'] ); ?></h3>
		<a href="<?php echo esc_url( GFSC_Data::entries_url( $gfsc_form['id'] ) ); ?>">
			<?php
			/* translators: %s: total entry count. */
			echo esc_html( sprintf( _n( 'View %s entry', 'View all %s entries', $gfsc_recent['total'], 'submission-chart-add-on-for-gravity-forms' ), number_format_i18n( $gfsc_recent['total'] ) ) );
			?>
		</a>
	</div>
	<?php if ( $gfsc_recent['entries'] ) : ?>
		<table class="widefat striped gfsc-table">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html( $gfsc_recent['field_label'] ); ?></th>
					<th scope="col" class="gfsc-col-date"><?php esc_html_e( 'Submitted', 'submission-chart-add-on-for-gravity-forms' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $gfsc_recent['entries'] as $gfsc_entry ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( $gfsc_entry['url'] ); ?>">
								<?php echo '' !== $gfsc_entry['summary'] ? esc_html( $gfsc_entry['summary'] ) : esc_html( sprintf( '#%d', $gfsc_entry['id'] ) ); ?>
							</a>
							<?php if ( $gfsc_can_spam || $gfsc_can_trash ) : ?>
								<div class="row-actions">
									<?php if ( $gfsc_can_spam ) : ?>
										<span class="spam"><a href="#" role="button" class="gfsc-entry-action" data-entry-id="<?php echo absint( $gfsc_entry['id'] ); ?>" data-status="spam" aria-label="<?php esc_attr_e( 'Mark this entry as spam', 'submission-chart-add-on-for-gravity-forms' ); ?>"><?php esc_html_e( 'Spam', 'submission-chart-add-on-for-gravity-forms' ); ?></a></span>
									<?php endif; ?>
									<?php if ( $gfsc_can_spam && $gfsc_can_trash ) : ?>
										|
									<?php endif; ?>
									<?php if ( $gfsc_can_trash ) : ?>
										<span class="trash"><a href="#" role="button" class="gfsc-entry-action" data-entry-id="<?php echo absint( $gfsc_entry['id'] ); ?>" data-status="trash" aria-label="<?php esc_attr_e( 'Move this entry to the trash', 'submission-chart-add-on-for-gravity-forms' ); ?>"><?php esc_html_e( 'Trash', 'submission-chart-add-on-for-gravity-forms' ); ?></a></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</td>
						<td class="gfsc-col-date"><?php echo esc_html( $gfsc_entry['date'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p class="gfsc-card-empty"><?php esc_html_e( 'No submissions yet.', 'submission-chart-add-on-for-gravity-forms' ); ?></p>
	<?php endif; ?>
</section>
