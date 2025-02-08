<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use PR\DHL\REST_API\Parcel_DE\Item_Info;
use PR\DHL\Utils\API_Utils;
use PR\DHL\REST_API\Parcel_DE_MyAccount;

class PR_DHL_API_Paket extends PR_DHL_API {

	const DHL_PAKET_DISPLAY_DAYS = 5;
	const DHL_PAKET_REMOVE_DAYS  = 2;

	protected $dhl_my_account = null;

	private $de_national_holidays = array( '2020-11-22', '2020-11-29', '2020-12-06', '2020-12-13', '2020-12-20', '2020-12-25', '2020-12-26', '2020-12-27', '2021-01-01', '2021-04-02', '2021-04-04', '2021-04-05', '2021-05-01', '2021-05-13', '2021-05-23', '2021-05-24', '2021-10-03', '2021-12-25', '2021-12-26', '2022-01-01', '2022-04-15', '2022-04-17', '2022-04-18', '2022-05-01', '2022-05-26', '2022-06-05', '2022-06-06', '2022-10-03', '2022-12-25', '2022-12-26', '2023-01-01', '2023-04-07', '2023-04-09', '2023-04-10', '2023-05-01', '2023-05-18', '2023-05-28', '2023-05-29', '2023-10-03', '2023-12-25', '2023-12-26', '2024-01-01', '2024-03-29', '2024-03-31', '2024-04-01', '2024-05-01', '2024-05-09', '2024-05-19', '2024-05-20', '2024-10-03', '2024-12-25', '2024-12-26' );

	public function __construct( $country_code ) {
		$this->country_code = $country_code;
		$settings           = $this->get_settings();

		try {
			if ( API_Utils::is_new_merchant() || API_Utils::is_rest_api_enabled() ) {
				$this->dhl_label = new PR_DHL_API_REST_Parcel_DE( $country_code );
			} else {
				$this->dhl_label = new PR_DHL_API_SOAP_Label();
			}
			// $this->dhl_finder = new PR_DHL_API_SOAP_Finder( );
			$this->dhl_finder     = new PR_DHL_API_REST_Finder();
			$this->dhl_my_account = new PR_DHL_API_REST_Parcel_DE_MyAccount();
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	public function is_dhl_paket() {
		return true;
	}

	protected function maybe_sandbox( $args ) {

		if ( isset( $args['sandbox'] ) && ( $args['sandbox'] == 'yes' ) ) {
			$sandbox_info = $this->sandbox_info();
			$settings     = $this->get_settings();

			$args['api_user'] = $sandbox_info['username'];
			$args['api_pwd']  = $sandbox_info['pass'];

			if ( API_Utils::is_new_merchant() || API_Utils::is_rest_api_enabled() ) {
				$args['account_num'] = $sandbox_info['rest_api_account_no'];
			} else {
				$args['account_num'] = $sandbox_info['account_no'];
			}
		}

		return $args;
	}

	public function get_dhl_label( $args ) {
		$args['dhl_settings'] = $this->maybe_sandbox( $args['dhl_settings'] );
		return parent::get_dhl_label( $args );
	}

	/**
	 * Create multiple labels in 1 API call.
	 *
	 * @param array[] $multiple_orders_args Set of orders args.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_dhl_labels( $multiple_orders_args ) {
		$items_info = array();
		$labels     = array();
		$uom        = get_option( 'woocommerce_weight_unit' );

		foreach ( $multiple_orders_args as $args ) {
			$args['dhl_settings'] = $this->maybe_sandbox( $args['dhl_settings'] );
			try {
				$this->dhl_label->set_arguments( $args );
				$items_info[] = new Item_Info( $args, $uom );
			} catch ( Exception $e ) {
				$labels['errors'][] = array(
					'order_id' => $args['order_details']['order_id'],
					'message'  => $e->getMessage(),
				);
			}
		}

		// Create the item and get the barcode
		if ( ! empty( $items_info ) ) {
			$items = $this->dhl_label->api_client->create_items( $items_info );
			foreach ( $items['items'] as $item ) {
				$order_id = (int) str_replace( apply_filters( 'pr_shipping_dhl_paket_label_ref_no_prefix', 'order_' ), '', $item->shipmentRefNo );
				if ( isset( $item->label->b64 ) ) {
					$file               = $this->dhl_label->save_data_file( 'label', $order_id, $item->label->b64 );
					$labels['labels'][] = array(
						'order_id'        => $order_id,
						'label_path'      => $file['label_path'],
						'tracking_number' => $item->shipmentNo,
						'tracking_status' => '',
					);
				}
			}

			foreach ( $items['errors'] as $error ) {
				$labels['errors'][] = array(
					'order_id' => $error['order_id'],
					'message'  => $error['message'],
				);
			}
		}

		return $labels;
	}

	public function delete_dhl_label( $args ) {
		return parent::delete_dhl_label( $this->maybe_sandbox( $args ) );
	}

	public function get_parcel_location( $args ) {
		$args['dhl_settings'] = $this->maybe_sandbox( $args['dhl_settings'] );
		return parent::get_parcel_location( $args );
	}

	/**
	 * Retrieves all of the Deutsche Post settings.
	 *
	 * @since [*next-version*]
	 *
	 * @return array An associative array of the settings keys mapping to their values.
	 */
	public function get_settings() {
		return get_option( 'woocommerce_pr_dhl_paket_settings', array() );
	}

	public function set_dhl_myaccount_info( $value ) {
		update_option( 'woocommerce_pr_dhl_paket_settings', $value );
	}

	public function get_dhl_myaccount_pwd_expiration() {
		return get_option( 'wc_dhl_myaccount_pwd_expiration', '' );
	}

	public function set_dhl_myaccount_pwd_expiration( $value ) {
		update_option( 'wc_dhl_myaccount_pwd_expiration', $value );
	}

	public function get_dhl_booking_text() {
		return get_option( 'wc_dhl_booking_text', '' );
	}

	public function set_dhl_booking_text( $value ) {
		update_option( 'wc_dhl_booking_text', $value );
	}

	public function get_dhl_products_international() {
		$country_code = $this->country_code;

		$germany_int = array(
			'V55PAK'  => esc_html__( 'DHL Paket Connect', 'dhl-for-woocommerce' ),
			'V54EPAK' => esc_html__( 'DHL Europaket (B2B)', 'dhl-for-woocommerce' ),
			'V53WPAK' => esc_html__( 'DHL Paket International', 'dhl-for-woocommerce' ),
			'V66WPI'  => esc_html__( 'DHL Warenpost International', 'dhl-for-woocommerce' ),
		);

		$dhl_prod_int = array();

		switch ( $country_code ) {
			case 'DE':
				$dhl_prod_int = $germany_int;
				break;
			default:
				break;
		}

		return apply_filters( 'pr_shipping_dhl_paket_products_international', $dhl_prod_int );
	}

	public function get_dhl_products_domestic() {
		$country_code = $this->country_code;

		$germany_dom = array(
			'V01PAK'  => esc_html__( 'DHL Paket', 'dhl-for-woocommerce' ),
			'V01PRIO' => esc_html__( 'DHL Paket PRIO', 'dhl-for-woocommerce' ),
			'V62WP'   => esc_html__( 'DHL Warenpost National', 'dhl-for-woocommerce' ),
			'V62KP'   => esc_html__( 'DHL Kleinpaket', 'dhl-for-woocommerce' ),
		);

		$dhl_prod_dom = array();

		switch ( $country_code ) {
			case 'DE':
				$dhl_prod_dom = $germany_dom;
				break;
			default:
				break;
		}

		return apply_filters( 'pr_shipping_dhl_paket_products_domestic', $dhl_prod_dom );
	}

	public function get_dhl_preferred_day_time( $postcode, $account_num, $cutoff_time = '12:00', $exclude_working_days = array() ) {
		// Always exclude Sunday
		$exclude_sun           = array( 'Sun' => esc_html__( 'sun', 'dhl-for-woocommerce' ) );
		$exclude_working_days += $exclude_sun;
		$day_counter           = 0;

		// Get existing timezone to reset afterwards
		// $current_timzone = date_default_timezone_get();

		// Always set and get DE timezone and check against it.
		// date_default_timezone_set('Europe/Berlin');
		// Get existing time locale
		// $current_locale = setlocale(LC_TIME, 0);
		// Set time locale based on WP locale setting (Settings->General)
		// $wp_locale = get_locale();
		// setlocale(LC_TIME, $wp_locale);
		// setlocale(LC_TIME, 'de_DE', 'deu_deu', 'de_DE.utf8', 'German', 'deu/ger', 'de_DE@euro', 'de', 'ge');

		$tz_obj             = new DateTimeZone( 'Europe/Berlin' );
		$today              = new DateTime( 'now', $tz_obj );   // Should the order date be passed as a variable?
		$today_de_timestamp = $today->getTimestamp();

		$week_day  = $today->format( 'D' );
		$week_date = $today->format( 'Y-m-d' );
		$week_time = $today->format( 'H:i' );

		// Compare week day with key since key includes capital letter in beginning and will work for English AND German!
		// Check if today is a working day...
		if ( ( ! array_key_exists( $week_day, $exclude_working_days ) ) && ( ! in_array( $week_date, $this->de_national_holidays ) ) ) {
			// ... and check if after cutoff time if today is a transfer day
			if ( $today_de_timestamp >= strtotime( $cutoff_time ) ) {
				// If the cut off time has been passed, then add a day
				$today->add( new DateInterval( 'P1D' ) ); // Add 1 day
				$week_day  = $today->format( 'D' );
				$week_date = $today->format( 'Y-m-d' );

				++$day_counter;

			}
		}

		// Make sure the next transfer days are working days
		while ( array_key_exists( $week_day, $exclude_working_days ) || in_array( $week_date, $this->de_national_holidays ) ) {
			$today->add( new DateInterval( 'P1D' ) ); // Add 1 day
			$week_day  = $today->format( 'D' );
			$week_date = $today->format( 'Y-m-d' );

			++$day_counter;
		}

		$args['postcode']   = $postcode;
		$args['start_date'] = $week_date;
		$settings           = $this->get_settings();

		if ( isset( $settings['dhl_sandbox'] ) && ( $settings['dhl_sandbox'] == 'yes' ) ) {
			$sandbox_info        = $this->sandbox_info();
			$args['account_num'] = $sandbox_info['account_no'];
		} else {
			$args['account_num'] = $account_num;
		}

		$dhl_parcel_services = new PR_DHL_API_REST_Parcel();
		$preferred_services  = $dhl_parcel_services->get_dhl_parcel_services( $args );

		$preferred_day_time                  = array();
		$preferred_day_time['preferred_day'] = $this->get_dhl_preferred_day( $preferred_services );

		// Reset time locael
		// setlocale(LC_TIME, $current_locale);
		// Reset timezone to not affect any other plugins
		// date_default_timezone_set($current_timzone);

		return $preferred_day_time;
	}

	protected function get_dhl_preferred_day( $preferred_services ) {
		$day_of_week_arr = array(
			'1' => esc_html__( 'Mon', 'dhl-for-woocommerce' ),
			'2' => esc_html__( 'Tue', 'dhl-for-woocommerce' ),
			'3' => esc_html__( 'Wed', 'dhl-for-woocommerce' ),
			'4' => esc_html__( 'Thu', 'dhl-for-woocommerce' ),
			'5' => esc_html__( 'Fri', 'dhl-for-woocommerce' ),
			'6' => esc_html__( 'Sat', 'dhl-for-woocommerce' ),
			'7' => esc_html__( 'Sun', 'dhl-for-woocommerce' ),
		);

		$preferred_days = array();
		if ( isset( $preferred_services->preferredDay->available ) && $preferred_services->preferredDay->available && isset( $preferred_services->preferredDay->validDays ) ) {

			foreach ( $preferred_services->preferredDay->validDays as $days_key => $days_value ) {
				$temp_day_time = strtotime( $days_value->start );

				$day_of_week = gmdate( 'N', $temp_day_time );
				$week_date   = gmdate( 'Y-m-d', $temp_day_time );

				$preferred_days[ $week_date ] = $day_of_week_arr[ $day_of_week ];
			}

			// Add none option
			array_unshift( $preferred_days, esc_html__( 'none', 'dhl-for-woocommerce' ) );
		}

		return $preferred_days;
	}

	public function get_dhl_duties() {
		$duties = parent::get_dhl_duties();

		$duties_paket = array(
			'DXV' => esc_html__( 'Delivery Duty Paid (excl. VAT )', 'dhl-for-woocommerce' ),
			'DDX' => esc_html__( 'Delivery Duty Paid (excl. Duties, taxes and VAT)', 'dhl-for-woocommerce' ),
		);
		$duties      += $duties_paket;

		return $duties;
	}

	public function get_dhl_visual_age() {
		$visual_age = array(
			'0'   => _x( 'none', 'age context', 'dhl-for-woocommerce' ),
			'A16' => esc_html__( 'Minimum age of 16', 'dhl-for-woocommerce' ),
			'A18' => esc_html__( 'Minimum age of 18', 'dhl-for-woocommerce' ),
		);
		return $visual_age;
	}

	public function sandbox_info() {
		return array(
			'username'            => '2222222222_01',
			'pass'                => 'pass',
			'account_no'          => '2222222222',
			'rest_api_account_no' => '3333333333',
		);
	}

	public function get_my_account() {
		return $this->dhl_my_account->get_dhl_my_account();
	}
}
