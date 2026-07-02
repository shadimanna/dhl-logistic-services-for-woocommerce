<?php

use PR\DHL\REST_API\Drivers\JSON_API_Driver;
use PR\DHL\REST_API\Drivers\Logging_Driver;
use PR\DHL\REST_API\Drivers\WP_API_Driver;
use PR\DHL\REST_API\Internetmarke\Auth;
use PR\DHL\REST_API\Internetmarke\Client;

if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_API_Internetmarke', false ) ) {
	return;
}

class PR_DHL_API_Internetmarke {
	const API_URL = 'https://api-eu.dhl.com/post/de/shipping/im/v1';

	const ACCESS_TOKEN_TRANSIENT = 'pr_dhl_internetmarke_access_token';

	/**
	 * Polymorphic discriminators for the shopping-cart PDF checkout request.
	 *
	 * The request body and its positions are polymorphic: the root needs `type`
	 * and each position needs `positionType`. Values verified against the
	 * INTERNETMARKE OpenAPI spec and confirmed live (a correct body reaches the
	 * payment step / walletBalanceNotEnough instead of InvalidTypeIdException).
	 */
	const REQUEST_TYPE  = 'AppShoppingCartPDFRequest';
	const POSITION_TYPE = 'AppShoppingCartPDFPosition';

	/**
	 * Default print format id (plain A4). Available formats: GET /app/catalog?types=PAGE_FORMATS.
	 * Should be made configurable in a follow-up.
	 */
	const PAGE_FORMAT_ID = 1;

	protected $driver;

	protected $auth;

	protected $client;

	public function __construct() {
		$raw_driver     = new WP_API_Driver();
		$logging_driver = new Logging_Driver( PR_DHL(), $raw_driver );
		$this->driver   = new JSON_API_Driver( $logging_driver );

		// Auth uses the non-logging driver so the token request body (which carries
		// the Portokasse password and app secret) is never written to the log.
		$this->auth = new Auth(
			$raw_driver,
			static::API_URL,
			$this->get_app_client_id(),
			$this->get_app_client_secret(),
			$this->get_username(),
			$this->get_password(),
			static::ACCESS_TOKEN_TRANSIENT
		);
		$this->client = new Client( static::API_URL, $this->driver, $this->auth );
	}

	public function test_connection() {
		$this->validate_configuration();
		$this->log( 'Testing INTERNETMARKE connection.' );

		$token = $this->auth->test_connection();

		$this->auth->save_token( $token );
		$this->log( 'INTERNETMARKE connection test succeeded.' );

		return $token;
	}

	public function validate_configuration() {
		if ( '' === $this->get_username() || '' === $this->get_password() ) {
			throw new Exception( esc_html__( 'Please save the INTERNETMARKE Portokasse username and password before running this action.', 'dhl-for-woocommerce' ) );
		}
	}

	protected function get_app_client_id() {
		return defined( 'PR_DHL_GLOBAL_API' ) ? PR_DHL_GLOBAL_API : '';
	}

	protected function get_app_client_secret() {
		return defined( 'PR_DHL_GLOBAL_SECRET' ) ? PR_DHL_GLOBAL_SECRET : '';
	}

	protected function get_settings() {
		$paket = get_option( 'woocommerce_pr_dhl_paket_settings', array() );
		return array(
			'internetmarke_api_user'      => isset( $paket['internetmarke_api_user'] ) ? $paket['internetmarke_api_user'] : '',
			'internetmarke_api_password'  => isset( $paket['internetmarke_api_password'] ) ? $paket['internetmarke_api_password'] : '',
			'internetmarke_portokasse_id' => isset( $paket['internetmarke_portokasse_id'] ) ? $paket['internetmarke_portokasse_id'] : '',
		);
	}

	protected function get_username() {
		$settings = $this->get_settings();
		return trim( (string) $settings['internetmarke_api_user'] );
	}

	protected function get_password() {
		$settings = $this->get_settings();
		return (string) $settings['internetmarke_api_password'];
	}

	/**
	 * Generate an INTERNETMARKE label for a WooCommerce order.
	 *
	 * @param int $order_id   WooCommerce order ID.
	 * @param int $product_id Resolved INTERNETMARKE product ID.
	 * @return array { label_url: string, tracking_number: string }
	 * @throws Exception
	 */
	public function generate_label( $order_id, $product_id ) {
		$this->validate_configuration();
		$this->log( 'Generating label for order ' . $order_id . ', product ID ' . $product_id . '.' );

		$order = wc_get_order( $order_id );
		if ( ! is_a( $order, 'WC_Order' ) ) {
			throw new Exception( esc_html__( 'Could not load the WooCommerce order for INTERNETMARKE label generation.', 'dhl-for-woocommerce' ) );
		}

		$payload = $this->build_label_payload( $order, $product_id );
		$result  = $this->client->create_label( $payload );

		$label_url       = '';
		$tracking_number = '';

		if ( is_object( $result ) ) {
			if ( ! empty( $result->link ) ) {
				$label_url = $this->download_and_save_label( (string) $result->link, $order_id );
			}
			// CheckoutShoppingCartPDFResponse returns vouchers under shoppingCart->voucherList;
			// trackId is present per voucher for registered products (EINSCHREIBEN).
			$vouchers = isset( $result->shoppingCart->voucherList ) ? $result->shoppingCart->voucherList : array();
			if ( ! empty( $vouchers ) && is_array( $vouchers ) ) {
				foreach ( $vouchers as $voucher ) {
					if ( ! empty( $voucher->trackId ) ) {
						$tracking_number = (string) $voucher->trackId;
						break;
					}
				}
			}
		}

		if ( '' === $label_url ) {
			$this->log( 'Label checkout for order ' . $order_id . ' returned no document link.' );
			throw new Exception( esc_html__( 'The label was purchased but no document could be retrieved from INTERNETMARKE. Check your Portokasse before retrying to avoid a duplicate charge.', 'dhl-for-woocommerce' ) );
		}

		$this->log( 'Label generated for order ' . $order_id . '.' );

		// Omit tracking_number when empty — most products don't return a trackId.
		return array_filter(
			array(
				'label_url'       => $label_url,
				'tracking_number' => $tracking_number,
			),
			'strlen'
		);
	}

	/**
	 * Build the AppShoppingCartPDFRequest payload for POST /app/shoppingcart/pdf?directCheckout=true.
	 *
	 * Structure verified live against the API: a correct body reaches the payment
	 * step (walletBalanceNotEnough on an unfunded dev Portokasse). `total` must equal
	 * the voucher price in euro-cents, so it is looked up per product code.
	 *
	 * @param WC_Order $order
	 * @param int      $product_id Resolved INTERNETMARKE product code.
	 * @return array
	 */
	protected function build_label_payload( WC_Order $order, $product_id ) {
		return array(
			'type'               => self::REQUEST_TYPE,
			'shopOrderId'        => (string) $order->get_id(),
			'pageFormatId'       => self::PAGE_FORMAT_ID,
			'total'              => $this->get_product_price_cents( $product_id ),
			'createManifest'     => false,
			'createShippingList' => '0',
			'dpi'                => 'DPI300',
			'positions'          => array(
				array(
					'positionType' => self::POSITION_TYPE,
					'productCode'  => (int) $product_id,
					'voucherLayout' => 'ADDRESS_ZONE',
					'position'     => array(
						'labelX' => 1,
						'labelY' => 1,
						'page'   => 1,
					),
					'address'      => array(
						'sender'   => $this->get_sender_address(),
						'receiver' => $this->get_recipient_address( $order ),
					),
				),
			),
		);
	}

	/**
	 * Voucher price in euro-cents for a resolved INTERNETMARKE product code.
	 *
	 * Prices taken from the official Deutsche Post product price list (ppl_v590_PARTNER),
	 * covering the approved product scope. `total` is validated by the API on checkout,
	 * so these must stay in sync with the current PPL — ideally synced from the catalog
	 * API in a follow-up rather than hard-coded.
	 *
	 * @param  int $product_id Resolved INTERNETMARKE product code.
	 * @return int             Price in euro-cents.
	 * @throws Exception       If no price is configured for the product.
	 */
	protected function get_product_price_cents( $product_id ) {
		static $prices = array(
			11    => 110,  21    => 180,  31    => 290,  41    => 510,  290   => 270,  331   => 355,
			1012  => 345,  1017  => 375,  1018  => 595,
			1022  => 415,  1027  => 445,  1028  => 665,
			1032  => 525,  1037  => 555,  1038  => 775,
			1042  => 745,  1047  => 775,  1048  => 995,
			10011 => 180,  10051 => 330,  10071 => 650,  10091 => 1700,
			11016 => 550,  11056 => 700,  11076 => 1020, 11096 => 2070,
		);

		$id = (int) $product_id;

		if ( ! isset( $prices[ $id ] ) ) {
			throw new Exception(
				sprintf(
					/* translators: %d: INTERNETMARKE product code. */
					esc_html__( 'No price is configured for INTERNETMARKE product %d.', 'dhl-for-woocommerce' ),
					$id
				)
			);
		}

		return $prices[ $id ];
	}

	/**
	 * Download the label PDF from Deutsche Post's URL and save it locally.
	 *
	 * Deutsche Post returns a time-limited link (~24 h–7 days). Storing the remote URL
	 * directly means the merchant's "Download Label" link will break after expiry. Instead
	 * we download the PDF immediately and store it in the same woocommerce_dhl_label/
	 * directory used by DHL Paket labels, so labels are accessible permanently.
	 *
	 * @param  string $remote_url URL returned in the API response link field.
	 * @param  int    $order_id   WooCommerce order ID (used for the filename).
	 * @return string             Local web-accessible URL to the saved PDF.
	 * @throws Exception          On HTTP error or file-system failure.
	 */
	protected function download_and_save_label( $remote_url, $order_id ) {
		PR_DHL()->dhl_label_folder_check();

		$dir = PR_DHL()->get_dhl_label_folder_dir();
		$web = PR_DHL()->get_dhl_label_folder_url();

		if ( empty( $dir ) ) {
			throw new Exception( esc_html__( 'Could not create the DHL label folder.', 'dhl-for-woocommerce' ) );
		}

		$filename  = 'dhl-im-label-' . (int) $order_id . '.pdf';
		$file_path = $dir . $filename;
		$file_url  = $web . $filename;

		// validate_file() returns 2 for absolute paths on Windows — that is safe.
		if ( validate_file( $file_path ) > 0 && validate_file( $file_path ) !== 2 ) {
			throw new Exception( esc_html__( 'Invalid INTERNETMARKE label file path.', 'dhl-for-woocommerce' ) );
		}

		$response = wp_remote_get( $remote_url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			$this->log( 'Label PDF download error for order ' . $order_id . ': ' . $response->get_error_message() );
			throw new Exception( $response->get_error_message() );
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $http_code ) {
			$this->log( 'Label PDF download for order ' . $order_id . ' returned HTTP ' . $http_code . '.' );
			throw new Exception(
				sprintf(
					/* translators: %d: HTTP status code */
					esc_html__( 'Could not download INTERNETMARKE label (HTTP %d).', 'dhl-for-woocommerce' ),
					$http_code
				)
			);
		}

		$pdf = wp_remote_retrieve_body( $response );

		if ( empty( $pdf ) ) {
			throw new Exception( esc_html__( 'Empty response when downloading INTERNETMARKE label.', 'dhl-for-woocommerce' ) );
		}

		$written = file_put_contents( $file_path, $pdf ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( false === $written || 0 === $written ) {
			throw new Exception( esc_html__( 'Could not save INTERNETMARKE label file.', 'dhl-for-woocommerce' ) );
		}

		$this->log( 'Label PDF saved locally for order ' . $order_id . ': ' . $filename . ' (' . strlen( $pdf ) . ' bytes).' );

		return $file_url;
	}

	/**
	 * Build the sender address from Paket shipper settings with WC store address as fallback.
	 *
	 * @return array
	 */
	protected function get_sender_address() {
		$paket = get_option( 'woocommerce_pr_dhl_paket_settings', array() );

		$name   = ! empty( $paket['dhl_shipper_name'] ) ? $paket['dhl_shipper_name'] : get_bloginfo( 'name' );
		$street = ! empty( $paket['dhl_shipper_address'] ) ? $paket['dhl_shipper_address'] : '';
		$house  = ! empty( $paket['dhl_shipper_address_no'] ) ? $paket['dhl_shipper_address_no'] : '';
		$city   = ! empty( $paket['dhl_shipper_address_city'] ) ? $paket['dhl_shipper_address_city'] : get_option( 'woocommerce_store_city', '' );
		$zip    = ! empty( $paket['dhl_shipper_address_zip'] ) ? $paket['dhl_shipper_address_zip'] : get_option( 'woocommerce_store_postcode', '' );

		$line1 = trim( $street . ' ' . $house );
		if ( '' === $line1 ) {
			$line1 = get_option( 'woocommerce_store_address', '' );
		}

		$address = array(
			'name'         => (string) $name,
			'addressLine1' => (string) $line1,
			'postalCode'   => (string) $zip,
			'city'         => (string) $city,
			'country'      => 'DEU', // Sender is always in Germany for INTERNETMARKE.
		);

		return array_filter( $address, 'strlen' );
	}

	/**
	 * Build the recipient address from the WC order shipping address.
	 *
	 * @param WC_Order $order
	 * @return array
	 */
	protected function get_recipient_address( WC_Order $order ) {
		$shipping = $order->get_address( 'shipping' );
		$billing  = $order->get_address();

		$first_name = ! empty( $shipping['first_name'] ) ? $shipping['first_name'] : ( isset( $billing['first_name'] ) ? $billing['first_name'] : '' );
		$last_name  = ! empty( $shipping['last_name'] ) ? $shipping['last_name'] : ( isset( $billing['last_name'] ) ? $billing['last_name'] : '' );
		$company    = ! empty( $shipping['company'] ) ? $shipping['company'] : '';
		$name       = trim( $first_name . ' ' . $last_name );

		// A person name goes in `name` and the company in `additionalName`; when only a
		// company is given it becomes the name.
		if ( '' === $name ) {
			$name    = $company;
			$company = '';
		}

		$address_1 = ! empty( $shipping['address_1'] ) ? $shipping['address_1'] : ( isset( $billing['address_1'] ) ? $billing['address_1'] : '' );
		$address_2 = ! empty( $shipping['address_2'] ) ? $shipping['address_2'] : '';
		$postcode  = ! empty( $shipping['postcode'] ) ? $shipping['postcode'] : ( isset( $billing['postcode'] ) ? $billing['postcode'] : '' );
		$city      = ! empty( $shipping['city'] ) ? $shipping['city'] : ( isset( $billing['city'] ) ? $billing['city'] : '' );
		$country   = ! empty( $shipping['country'] ) ? $shipping['country'] : ( isset( $billing['country'] ) ? $billing['country'] : 'DE' );

		$country_iso3 = $this->country_iso2_to_iso3( $country );
		if ( 3 !== strlen( $country_iso3 ) ) {
			throw new Exception(
				sprintf(
					/* translators: %s: two-letter destination country code. */
					esc_html__( 'INTERNETMARKE does not support the destination country "%s".', 'dhl-for-woocommerce' ),
					sanitize_text_field( (string) $country )
				)
			);
		}

		$address = array(
			'name'           => $name,
			'additionalName' => $company,
			'addressLine1'   => $address_1,
			'addressLine2'   => $address_2,
			'postalCode'     => $postcode,
			'city'           => $city,
			'country'        => $country_iso3,
		);

		return array_filter( $address, 'strlen' );
	}

	/**
	 * Convert ISO 3166-1 alpha-2 to alpha-3 (required by INTERNETMARKE API).
	 *
	 * Complete mapping covering all UN member states and commonly-used territories.
	 *
	 * @param  string $iso2 Two-letter country code.
	 * @return string       Three-letter country code, or original value if unknown.
	 */
	protected function country_iso2_to_iso3( $iso2 ) {
		static $map = array(
			// Europe
			'AD' => 'AND', 'AL' => 'ALB', 'AM' => 'ARM', 'AT' => 'AUT', 'AZ' => 'AZE',
			'BA' => 'BIH', 'BE' => 'BEL', 'BG' => 'BGR', 'BY' => 'BLR', 'CH' => 'CHE',
			'CY' => 'CYP', 'CZ' => 'CZE', 'DE' => 'DEU', 'DK' => 'DNK', 'EE' => 'EST',
			'ES' => 'ESP', 'FI' => 'FIN', 'FR' => 'FRA', 'GB' => 'GBR', 'GE' => 'GEO',
			'GR' => 'GRC', 'HR' => 'HRV', 'HU' => 'HUN', 'IE' => 'IRL', 'IS' => 'ISL',
			'IT' => 'ITA', 'LI' => 'LIE', 'LT' => 'LTU', 'LU' => 'LUX', 'LV' => 'LVA',
			'MC' => 'MCO', 'MD' => 'MDA', 'ME' => 'MNE', 'MK' => 'MKD', 'MT' => 'MLT',
			'NL' => 'NLD', 'NO' => 'NOR', 'PL' => 'POL', 'PT' => 'PRT', 'RO' => 'ROU',
			'RS' => 'SRB', 'RU' => 'RUS', 'SE' => 'SWE', 'SI' => 'SVN', 'SK' => 'SVK',
			'SM' => 'SMR', 'TR' => 'TUR', 'UA' => 'UKR', 'VA' => 'VAT', 'XK' => 'XKX',
			// Asia
			'AE' => 'ARE', 'AF' => 'AFG', 'BD' => 'BGD', 'BH' => 'BHR', 'BN' => 'BRN',
			'BT' => 'BTN', 'CN' => 'CHN', 'HK' => 'HKG', 'ID' => 'IDN', 'IL' => 'ISR',
			'IN' => 'IND', 'IQ' => 'IRQ', 'IR' => 'IRN', 'JO' => 'JOR', 'JP' => 'JPN',
			'KG' => 'KGZ', 'KH' => 'KHM', 'KP' => 'PRK', 'KR' => 'KOR', 'KW' => 'KWT',
			'KZ' => 'KAZ', 'LA' => 'LAO', 'LB' => 'LBN', 'LK' => 'LKA', 'MM' => 'MMR',
			'MN' => 'MNG', 'MO' => 'MAC', 'MV' => 'MDV', 'MY' => 'MYS', 'NP' => 'NPL',
			'OM' => 'OMN', 'PH' => 'PHL', 'PK' => 'PAK', 'PS' => 'PSE', 'QA' => 'QAT',
			'SA' => 'SAU', 'SG' => 'SGP', 'SY' => 'SYR', 'TH' => 'THA', 'TJ' => 'TJK',
			'TL' => 'TLS', 'TM' => 'TKM', 'TW' => 'TWN', 'UZ' => 'UZB', 'VN' => 'VNM',
			'YE' => 'YEM',
			// North & Central America
			'AG' => 'ATG', 'BB' => 'BRB', 'BS' => 'BHS', 'BZ' => 'BLZ', 'CA' => 'CAN',
			'CR' => 'CRI', 'CU' => 'CUB', 'DM' => 'DMA', 'DO' => 'DOM', 'GD' => 'GRD',
			'GT' => 'GTM', 'HN' => 'HND', 'HT' => 'HTI', 'JM' => 'JAM', 'KN' => 'KNA',
			'LC' => 'LCA', 'MX' => 'MEX', 'NI' => 'NIC', 'PA' => 'PAN', 'PR' => 'PRI',
			'SV' => 'SLV', 'TT' => 'TTO', 'US' => 'USA', 'VC' => 'VCT',
			// South America
			'AR' => 'ARG', 'BO' => 'BOL', 'BR' => 'BRA', 'CL' => 'CHL', 'CO' => 'COL',
			'EC' => 'ECU', 'GY' => 'GUY', 'PE' => 'PER', 'PY' => 'PRY', 'SR' => 'SUR',
			'UY' => 'URY', 'VE' => 'VEN',
			// Africa
			'AO' => 'AGO', 'BF' => 'BFA', 'BI' => 'BDI', 'BJ' => 'BEN', 'BW' => 'BWA',
			'CD' => 'COD', 'CF' => 'CAF', 'CG' => 'COG', 'CI' => 'CIV', 'CM' => 'CMR',
			'CV' => 'CPV', 'DJ' => 'DJI', 'DZ' => 'DZA', 'EG' => 'EGY', 'ER' => 'ERI',
			'ET' => 'ETH', 'GA' => 'GAB', 'GH' => 'GHA', 'GM' => 'GMB', 'GN' => 'GIN',
			'GQ' => 'GNQ', 'GW' => 'GNB', 'KE' => 'KEN', 'KM' => 'COM', 'LR' => 'LBR',
			'LS' => 'LSO', 'LY' => 'LBY', 'MA' => 'MAR', 'MG' => 'MDG', 'ML' => 'MLI',
			'MR' => 'MRT', 'MU' => 'MUS', 'MW' => 'MWI', 'MZ' => 'MOZ', 'NA' => 'NAM',
			'NE' => 'NER', 'NG' => 'NGA', 'RW' => 'RWA', 'SC' => 'SYC', 'SD' => 'SDN',
			'SL' => 'SLE', 'SN' => 'SEN', 'SO' => 'SOM', 'SS' => 'SSD', 'ST' => 'STP',
			'SZ' => 'SWZ', 'TD' => 'TCD', 'TG' => 'TGO', 'TN' => 'TUN', 'TZ' => 'TZA',
			'UG' => 'UGA', 'ZA' => 'ZAF', 'ZM' => 'ZMB', 'ZW' => 'ZWE',
			// Oceania
			'AU' => 'AUS', 'FJ' => 'FJI', 'FM' => 'FSM', 'KI' => 'KIR', 'MH' => 'MHL',
			'NR' => 'NRU', 'NZ' => 'NZL', 'PG' => 'PNG', 'PW' => 'PLW', 'SB' => 'SLB',
			'TO' => 'TON', 'TV' => 'TUV', 'VU' => 'VUT', 'WS' => 'WSM',
		);

		$iso2 = strtoupper( (string) $iso2 );
		return isset( $map[ $iso2 ] ) ? $map[ $iso2 ] : $iso2;
	}

	protected function log( $message ) {
		PR_DHL()->log_msg( '[INTERNETMARKE] ' . $message );
	}
}
