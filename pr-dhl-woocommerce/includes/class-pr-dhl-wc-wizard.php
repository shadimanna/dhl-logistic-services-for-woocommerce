<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce DHL Wizard.
 *
 * @package  PR_DHL_WC_Wizard
 * @category Admin
 * @author   Shadi Manna
 */

if ( ! class_exists( 'PR_DHL_WC_Wizard' ) ) :

class PR_DHL_WC_Wizard {

    public function __construct() {

		if ( true ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'wizard_enqueue_scripts') );
			add_action( 'admin_footer', array( $this, 'display_wizard' ), 10 );
		}
    }

	public function wizard_enqueue_scripts() {
		wp_enqueue_style( 'wc-shipment-lib-wizard-css', PR_DHL_PLUGIN_DIR_URL . '/assets/css/wizard.library.css' );
		wp_enqueue_style( 'wc-shipment-dhl-wizard-css', PR_DHL_PLUGIN_DIR_URL . '/assets/css/pr-dhl-wizard.css' );
		wp_enqueue_script(
			'wc-shipment-lib-wizard-js',
			PR_DHL_PLUGIN_DIR_URL . '/assets/js/wizard.library.js',
			array(),
			PR_DHL_VERSION
		);
		wp_enqueue_script(
			'wc-shipment-dhl-wizard-js',
			PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-wizard.js',
			array(),
			PR_DHL_VERSION,
			true
		);
	}

	public function display_wizard() {
	?>
		<div class="pr-dhl-wc-wizard-overlay">
			<div class="pr-dhl-wc-wizard-container">
				<div class="pr-dhl-wc-wizard">
					<div class="wizard-header">
						<img src="<?php echo esc_url( PR_DHL_PLUGIN_DIR_URL . '/assets/img/dhl-official.png' ) ?>" width="300" class="dhl-logo" alt="DHL Logo" />
					</div>
					<form class="wizard" id="dhlStepsWizard">
						<aside class="wizard-content container">
							<div class="wizard-step" data-title="Step 1">
								<div class="form-group">
									<label for="nameCampaign">Campaign name <span class="required">*</span></label>
									<input type="text" name="name" class="form-control required" id="nameCampaign" placeholder="Enter a short campaign name">
								</div>
								<div class="form-group">
									<label for="descCampaign">Description of the campaign</label>
									<textarea name="description" class="form-control" id="descCampaign"></textarea>
								</div>
							</div>

							<div class="wizard-step" data-title="Step 2">
								<div class="form-group">
									<label for="nameCampaign">Campaign name <span class="required">*</span></label>
									<input type="text" name="name" class="form-control required" id="nameCampaign" placeholder="Enter a short campaign name">
								</div>
								<div class="form-group">
									<label for="descCampaign">Description of the campaign</label>
									<textarea name="description" class="form-control" id="descCampaign"></textarea>
								</div>
							</div>
						</aside>
					</form>
				</div>
				<a href="#" class="pr-dhl-wc-skip-wizard"><?php _e( 'Skip this', 'dhl-for-woocommerce' ); ?></a>
			</div>
		</div>
	<?php 
	}
}

endif;