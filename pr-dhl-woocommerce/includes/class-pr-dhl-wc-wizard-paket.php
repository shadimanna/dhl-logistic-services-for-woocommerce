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

if ( ! class_exists( 'PR_DHL_WC_Wizard_Paket' ) ) :

class PR_DHL_WC_Wizard_Paket extends PR_DHL_WC_Wizard {
	public function init() {
		add_action( 'wp_ajax_save_wizard_fields', array( $this, 'save_wizard_fields' ) );
		add_action( 'wp_ajax_nopriv_save_wizard_fields', array( $this, 'save_wizard_fields' ) );
	}

	public function all_wizard_field_names() {
		return array(
			'dhl_account_num',
		);
	}

	public function save_wizard_fields() {
		$nonce = $_POST['nonce'];
		if ( ! wp_verify_nonce( $nonce, 'dhl-wizard-nonce' ) ) {
			wp_send_json_error( array( 'errortext' => __( 'Security check', 'dhl-for-woocommerce' ) ) );
		}
		
		$all_settings = get_option( 'woocommerce_pr_dhl_paket_settings', array() );
		$fields       = $_POST['fields'];

		foreach( $fields as $field ) {
			if ( in_array( $field['field'], $this->all_wizard_field_names() ) ) {
				$all_settings[ $field['field'] ] = $field['value'];
			}
		}

		update_option( 'woocommerce_pr_dhl_paket_settings', $all_settings );
		wp_send_json_success( array( 'message' => 'good' ) );
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
								<h4 class="wizard-title"><?php _e( 'Quick Set Up', 'dhl-for-woocommerce' ); ?></h4>
								<div class="wizard-description">
									<?php _e( 'Thank you for installing this plugin. We\'ll guide you through the set up and minimum required fields.', 'dhl-for-woocommerce' ); ?>
								</div>
								<input type="hidden" name="start_wizard" value="yes" />
								<div class="form-group">
									<button class="button-next"><?php _e( 'Begin Setup' , 'dhl-for-woocommerce' ); ?></button>
								</div>
							</div>

							<div class="wizard-step" data-title="Step 2">
								<h4 class="wizard-title"><?php _e( 'Account Number (EKP)', 'dhl-for-woocommerce' ); ?></h4>
								<div class="wizard-description">
									<?php _e( 'Your DHL account number (10 digits - numerical), also called "EKP". This will be provided by your local DHL sales organization.', 'dhl-for-woocommerce' ); ?>
								</div>
								<div class="form-group">
									<input type="text" name="dhl_account_num" class="form-control required wizard-dhl-field" id="wizard_dhl_account_num" placeholder="<?php _e( 'Enter your EKP', 'dhl-for-woocommerce' ); ?>">
								</div>
								<div class="form-group">
									<button class="button-next"><?php _e( 'Next' , 'dhl-for-woocommerce' ); ?></button>
								</div>
							</div>

							<div class="wizard-step" data-title="Step 3">
								<h4 class="wizard-title"><?php _e( 'Account Number (EKP)', 'dhl-for-woocommerce' ); ?></h4>
								<div class="wizard-description">
									<?php _e( 'Your DHL account number (10 digits - numerical), also called "EKP". This will be provided by your local DHL sales organization.', 'dhl-for-woocommerce' ); ?>
								</div>
								<div class="form-group">
									<input type="text" name="dhl_account_ss" class="form-control required wizard-dhl-field" id="wizard_dhl_account_ss" placeholder="<?php _e( 'Enter your EKP', 'dhl-for-woocommerce' ); ?>">
								</div>
								<div class="form-group">
									<button class="button-next"><?php _e( 'Next' , 'dhl-for-woocommerce' ); ?></button>
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