<?php
/**
 * Availability Settings Tab Template
 *
 * @package product-availability-checker
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Pagination setup
$current_page = $current_page ?? 1;
$per_page     = $per_page ?? 20;
$total_pages  = $total_pages ?? 1;
$total_codes  = $total_codes ?? 0;

// Pagination URLs
$base_url = admin_url( 'admin.php?page=wc-settings&tab=availability' );
?>

<div class="pavc-availability-settings wc-settings-prevent-change-event">
	<h2><?php echo esc_html( $page_title ); ?></h2>
	
	<div class="pavc-admin-toolbar">
		<button type="button" class="button button-primary" id="pavc-add-code">
			<?php esc_html_e( 'Add New Code', 'product-availability-checker' ); ?>
		</button>
		<div class="pavc-search-box">
			<input type="text" id="pavc-search-codes" placeholder="<?php esc_attr_e( 'Search codes...', 'product-availability-checker' ); ?>" />
		</div>
	</div>

	<div class="pavc-codes-table-wrapper">
		<table class="wp-list-table widefat fixed striped" id="pavc-codes-table">
			<thead>
				<tr>
					<th scope="col" class="column-id">
						<?php esc_html_e( 'ID', 'product-availability-checker' ); ?>
					</th>
					<th scope="col" class="column-code">
						<?php esc_html_e( 'Code', 'product-availability-checker' ); ?>
					</th>
					<th scope="col" class="column-message">
						<?php esc_html_e( 'Message', 'product-availability-checker' ); ?>
					</th>
					<th scope="col" class="column-actions">
						<?php esc_html_e( 'Actions', 'product-availability-checker' ); ?>
					</th>
				</tr>
			</thead>
			<tbody id="pavc-codes-tbody">
				<?php if ( ! empty( $codes ) ) : ?>
					<?php foreach ( $codes as $code ) : ?>
						<tr data-code-id="<?php echo esc_attr( $code['id'] ); ?>">
							<td class="column-id">
								<strong><?php echo esc_html( $code['id'] ); ?></strong>
							</td>
							<td class="column-code">
								<strong><?php echo esc_html( $code['zip_code'] ); ?></strong>
								<div>
									<span class="pavc-status pavc-status-<?php echo 'available' === $code['availability'] ? 'available' : 'unavailable'; ?>">
										<?php echo 'available' === $code['availability'] ? esc_html__( 'Available', 'product-availability-checker' ) : esc_html__( 'Unavailable', 'product-availability-checker' ); ?>
									</span>
								</div>
							</td>
							<td class="column-message">
								<?php
								$message = trim( $code['message'] ?? '' );
								if ( empty( $message ) ) {
									echo '<em style="color: #666;">' . esc_html__( 'No custom message set', 'product-availability-checker' ) . '</em>';
								} else {
									echo esc_html( $message );
								}
								?>
							</td>
							<td class="column-actions">
								<button type="button" class="button button-small pavc-edit-code" data-code-id="<?php echo esc_attr( $code['id'] ); ?>">
									<?php esc_html_e( 'Edit', 'product-availability-checker' ); ?>
								</button>
								<button type="button" class="button button-small button-link-delete pavc-delete-code" data-code-id="<?php echo esc_attr( $code['id'] ); ?>">
									<?php esc_html_e( 'Delete', 'product-availability-checker' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr class="no-items">
						<td class="colspanchange" colspan="4">
							<?php esc_html_e( 'No codes found.', 'product-availability-checker' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<div class="pavc-pagination-wrapper">
		<div class="tablenav bottom">
			<div class="alignleft actions">
				<span class="displaying-num">
					<?php
					printf(
						esc_html( _n( '%d code', '%d codes', $total_codes, 'product-availability-checker' ) ),
						$total_codes
					);
					?>
				</span>
			</div>
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav-pages">
					<span class="pagination-links">
						<?php if ( $current_page > 1 ) : ?>
							<a class="first-page button" href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'paged'    => 1,
										'per_page' => $per_page,
									),
									$base_url
								)
							);
							?>
							">
								<span class="screen-reader-text"><?php esc_html_e( 'First page', 'product-availability-checker' ); ?></span>
								<span aria-hidden="true">«</span>
							</a>
							<a class="prev-page button" href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'paged'    => max( 1, $current_page - 1 ),
										'per_page' => $per_page,
									),
									$base_url
								)
							);
							?>
							">
								<span class="screen-reader-text"><?php esc_html_e( 'Previous page', 'product-availability-checker' ); ?></span>
								<span aria-hidden="true">‹</span>
							</a>
						<?php else : ?>
							<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
							<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
						<?php endif; ?>

						<span class="paging-input">
							<span class="tablenav-paging-text"><?php echo esc_html( $current_page ); ?> of <span class="total-pages"><?php echo esc_html( $total_pages ); ?></span></span>
						</span>

						<?php if ( $current_page < $total_pages ) : ?>
							<a class="next-page button" href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'paged'    => min( $total_pages, $current_page + 1 ),
										'per_page' => $per_page,
									),
									$base_url
								)
							);
							?>
							">
								<span class="screen-reader-text"><?php esc_html_e( 'Next page', 'product-availability-checker' ); ?></span>
								<span aria-hidden="true">›</span>
							</a>
							<a class="last-page button" href="
							<?php
							echo esc_url(
								add_query_arg(
									array(
										'paged'    => $total_pages,
										'per_page' => $per_page,
									),
									$base_url
								)
							);
							?>
							">
								<span class="screen-reader-text"><?php esc_html_e( 'Last page', 'product-availability-checker' ); ?></span>
								<span aria-hidden="true">»</span>
							</a>
						<?php else : ?>
							<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
							<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
						<?php endif; ?>
					</span>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div><!-- Add/Edit Code Modal -->
<div id="pavc-code-modal" class="pavc-modal wc-settings-prevent-change-event" style="display: none;">
	<div class="pavc-modal-content">
		<div class="pavc-modal-header">
			<h3 id="pavc-modal-title"><?php esc_html_e( 'Add New Code', 'product-availability-checker' ); ?></h3>
			<button type="button" class="pavc-modal-close">&times;</button>
		</div>
		<div class="pavc-modal-body">
			<form id="pavc-code-form">
				<input type="hidden" id="pavc-code-id" name="code_id" value="" />
				
				<div class="pavc-form-field">
					<label for="pavc-code-input"><?php esc_html_e( 'Code', 'product-availability-checker' ); ?> <span class="required">*</span></label>
					<input type="text" id="pavc-code-input" name="code" required />
					<p class="description"><?php esc_html_e( 'Enter the zip/postal code.', 'product-availability-checker' ); ?></p>
				</div>

				<div class="pavc-form-field">
					<label for="pavc-status-select"><?php esc_html_e( 'Status', 'product-availability-checker' ); ?></label>
					<select id="pavc-status-select" name="is_available">
						<option value="available"><?php esc_html_e( 'Available', 'product-availability-checker' ); ?></option>
						<option value="unavailable"><?php esc_html_e( 'Unavailable', 'product-availability-checker' ); ?></option>
					</select>
				</div>

				<div class="pavc-form-field">
					<label for="pavc-message-input"><?php esc_html_e( 'Custom Message', 'product-availability-checker' ); ?></label>
					<textarea id="pavc-message-input" name="message" rows="3" placeholder="<?php esc_attr_e( 'Optional custom message for this code...', 'product-availability-checker' ); ?>"></textarea>
					<p class="description"><?php esc_html_e( 'Optional message to display for this specific code.', 'product-availability-checker' ); ?></p>
				</div>
			</form>
		</div>
		<div class="pavc-modal-footer">
			<button type="button" class="button button-secondary pavc-modal-cancel">
				<?php esc_html_e( 'Cancel', 'product-availability-checker' ); ?>
			</button>
			<button type="button" class="button button-primary" id="pavc-save-code">
				<?php esc_html_e( 'Save Code', 'product-availability-checker' ); ?>
			</button>
		</div>
	</div>
</div>

<!-- Loading Overlay -->
<div id="pavc-loading-overlay" class="pavc-loading-overlay" style="display: none;">
	<div class="pavc-loading-spinner">
		<div class="spinner is-active"></div>
		<p><?php esc_html_e( 'Loading...', 'product-availability-checker' ); ?></p>
	</div>
</div>
