<?php
/**
 * Availability Checker Widget Template
 *
 * @package product-availability-checker
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_id = $product_id ?? get_the_ID();
?>

<div class="pavc-availability-checker" id="pavc-availability-checker" data-product-id="<?php echo esc_attr( $product_id ); ?>">
	<h4 class="pavc-checker-title">
		<?php esc_html_e( 'Check Product Availability', 'product-availability-checker' ); ?>
	</h4>
	
	<div class="pavc-checker-form">
		<div class="pavc-input-group">
			<input 
				type="text" 
				id="pavc-zip-input" 
				class="pavc-zip-input" 
				placeholder="<?php esc_attr_e( 'Enter your zip code...', 'product-availability-checker' ); ?>"
				maxlength="10"
			/>
			<button 
				type="button" 
				id="pavc-check-btn" 
				class="pavc-check-button button"
			>
				<?php esc_html_e( 'Check Availability', 'product-availability-checker' ); ?>
			</button>
		</div>
		
		<div class="pavc-loading" id="pavc-loading" style="display: none;">
			<span class="pavc-spinner"></span>
			<span class="pavc-loading-text">
				<?php esc_html_e( 'Checking availability...', 'product-availability-checker' ); ?>
			</span>
		</div>
		
		<div class="pavc-result" id="pavc-result" style="display: none;">
			<div class="pavc-result-content">
				<span class="pavc-result-icon"></span>
				<div class="pavc-result-message">
					<strong class="pavc-status-text"></strong>
					<p class="pavc-message-text"></p>
				</div>
			</div>
		</div>
		
		<div class="pavc-error" id="pavc-error" style="display: none;">
			<span class="pavc-error-icon">⚠️</span>
			<span class="pavc-error-message"></span>
		</div>
	</div>
</div>