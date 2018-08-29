<?php
defined( 'ABSPATH' ) or exit;
/*

try {
  $shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
  $dhl_obj = PR_DHL()->get_dhl_factory();
} catch (Exception $e) {
    return;
}
*/
?>

<div style="display:none">
  <div id="dhl_parcel_finder_form">
    <!-- Create form and call via AJAX parcel finder API -->
    <form id="checkout_dhl_parcel_finder" method="post">

		<p class="form-row form-field small">
			<input type="text" name="dhl_parcelfinder_postcode" class="input-text" placeholder="<?php esc_attr_e( 'Post Code', 'pr-shipping-dhl' ); ?>" id="dhl_parcelfinder_postcode" />
		</p>

		<p class="form-row form-field small">
			<input type="text" name="dhl_parcelfinder_city" class="input-text" placeholder="<?php esc_attr_e( 'City', 'pr-shipping-dhl' ); ?>" id="dhl_parcelfinder_city" />
		</p>


		<p class="form-row form-field large">
			<input type="text" name="dhl_parcelfinder_address" class="input-text" placeholder="<?php esc_attr_e( 'Address', 'pr-shipping-dhl' ); ?>" id="dhl_parcelfinder_address" />
		</p>
		
		<!-- <div class="clear"></div> -->
		<?php if( $packstation_enabled ) : ?>
			<p class="form-row form-field packstation">
				<input type="checkbox" name="dhl_packstation_filter" class="input-checkbox" id="dhl_packstation_filter" value="1" checked />
				<label for="dhl_packstation_filter"><?php esc_attr_e( 'Packstation', 'pr-shipping-dhl' ); ?></label>
                <span class="icon" style="background-image: url('<?php echo $packstation_img; ?>');"></span>
			</p>
		<?php endif; ?>

		<?php if( $parcelshop_enabled || $post_office_enabled ) : ?>
			<p class="form-row form-field parcelshop">
				<input type="checkbox" name="dhl_branch_filter" class="input-checkbox" placeholder="" id="dhl_branch_filter" value="1" checked />
				<label for="dhl_branch_filter"><?php esc_attr_e( 'Branch', 'pr-shipping-dhl' ); ?></label>
                <span class="parcel-wrap">
                    <?php if( $parcelshop_enabled ) : ?>
                        <span class="icon" style="background-image: url('<?php echo $parcelshop_img; ?>');"></span>
                    <?php endif; ?>
                    <?php if( $post_office_enabled ) : ?>
                        <span class="icon" style="background-image: url('<?php echo $post_office_img; ?>');"></span>
                    <?php endif; ?>
                </span>
			</p>

		<?php endif; ?>
		
		<p id="dhl_seach_button" class="form-row form-field small">
			<input type="submit" class="button" name="apply_parcel_finder" value="<?php esc_attr_e( 'Search', 'pr-shipping-dhl' ); ?>" />
		</p>
		
		<input type="hidden" name="dhl_parcelfinder_country" id="dhl_parcelfinder_country" />

		<input type="hidden" name="dhl_parcelfinder_nonce" value="<?php echo wp_create_nonce('dhl_parcelfinder') ?>" />

		<div class="clear"></div>

		<?php // taken from fancybox documentation ?>
		<button data-fancybox-close class="fancybox-close-small" title="close"><svg viewBox="0 0 32 32"><path d="M10,10 L22,22 M22,10 L10,22"></path></svg></button>

	</form>

	<div id="dhl_google_map"></div>
	
  </div>
</div>