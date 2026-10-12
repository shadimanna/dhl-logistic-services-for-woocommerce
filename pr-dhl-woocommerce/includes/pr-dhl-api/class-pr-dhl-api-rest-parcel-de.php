<?php

use PR\DHL\REST_API\Parcel_DE\Auth;
use PR\DHL\REST_API\Parcel_DE\Client;
use PR\DHL\REST_API\Parcel_DE\Item_Info;
use PR\DHL\REST_API\Interfaces\API_Auth_Interface;
use PR\DHL\REST_API\Interfaces\API_Driver_Interface;

// Exit if accessed directly or class already exists
if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_API_Parcel_DE', false ) ) {
	return;
}

class PR_DHL_API_REST_Parcel_DE extends PR_DHL_API_REST_Paket {
	/**
	 * The URL to the API.
	 */
	const API_URL_PRODUCTION = 'https://api-eu.dhl.com/parcel/de/shipping/';

	/**
	 * The URL to the sandbox API.
	 */
	const API_URL_SANDBOX = 'https://api-sandbox.dhl.com/parcel/de/shipping/';

	/**
	 * The API driver instance.
	 *
	 * @var API_Driver_Interface
	 */
	public $api_driver;

	/**
	 * The API authorization instance.
	 *
	 * @var Auth
	 */
	public $api_auth;

	/**
	 * The API client instance.
	 *
	 * @var Client
	 */
	public $api_client;

	/**
	 * Whether a Paperless Customs upload has already been rate limited (HTTP 429) in this request.
	 *
	 * @since [*next-version*]
	 *
	 * @var bool
	 */
	protected $paperless_rate_limited = false;

	/**
	 * Whether DHL was unreachable (connection error, timeout or 5xx) for a Paperless Customs
	 * upload in this request. Once set, later orders skip the upload.
	 *
	 * @since [*next-version*]
	 *
	 * @var bool
	 */
	protected $paperless_unavailable = false;

	/**
	 * Constructor.
	 *
	 * @throws Exception If an error occurred while creating the API driver, auth or client.
	 */
	public function __construct( $country_code ) {
		parent::__construct( $country_code );
	}

	/**
	 * Initializes the API client instance.
	 *
	 * @return Client
	 *
	 * @throws Exception If failed to create the API client.
	 */
	protected function create_api_client() {
		// Create the API client, using this instance's driver and auth objects
		return new Client(
			$this->get_api_url(),
			$this->api_driver,
			$this->api_auth
		);
	}

	/**
	 * Upload a customs document (commercial invoice PDF) for Paperless Customs.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $pdf_bytes Raw PDF content.
	 * @param string $filename  Document filename (<= 60 chars).
	 * @param string $reference Optional user-defined reference.
	 * @return string The DHL batchId (UUID).
	 * @throws Exception On failure.
	 */
	public function upload_customs_documents( $pdf_bytes, $filename, $reference = '' ) {
		$filename  = sanitize_file_name( $filename );
		$reference = preg_replace( '/[\r\n]+/', ' ', (string) $reference );

		$boundary = wp_generate_password( 24, false );
		$eol      = "\r\n";

		$body  = '--' . $boundary . $eol;
		$body .= 'Content-Disposition: form-data; name="documents[0].documentType"' . $eol . $eol;
		$body .= 'Invoice' . $eol;

		if ( '' !== $reference ) {
			$body .= '--' . $boundary . $eol;
			$body .= 'Content-Disposition: form-data; name="documents[0].userDefinedReference"' . $eol . $eol;
			$body .= substr( $reference, 0, 60 ) . $eol;
		}

		$body .= '--' . $boundary . $eol;
		$body .= 'Content-Disposition: form-data; name="documents[0].data"; filename="' . $filename . '"' . $eol;
		$body .= 'Content-Type: application/pdf' . $eol . $eol;
		$body .= $pdf_bytes . $eol;
		$body .= '--' . $boundary . '--' . $eol;

		$url     = \PR\DHL\REST_API\URL_Utils::merge_url_and_route( $this->get_api_url(), $this->request_customs_documents_route() );
		$request = new \PR\DHL\REST_API\Request( \PR\DHL\REST_API\Request::TYPE_POST, $url, array(), $body );
		$request->headers['Content-Type']   = 'multipart/form-data; boundary=' . $boundary;
		$request->headers['Accept']         = 'application/json';

		// Reuse the same Basic + dhl-api-key auth as every other call.
		$request = $this->api_auth->authorize( $request );

		// Send via the RAW WP driver (NOT the JSON decorator, which would re-encode the body).
		$driver   = new \PR\DHL\REST_API\Drivers\WP_API_Driver();
		$response = $this->upload_with_retry( $driver, $request );

		$code = (int) $response->status;
		$body = json_decode( $response->body );

		if ( 201 === $code && isset( $body->batchId ) ) {
			return $body->batchId;
		}

		$detail = '';
		if ( is_object( $body ) ) {
			$detail = (string) ( $body->detail ?? ( $body->title ?? '' ) );
		}

		$message = sprintf(
			/* translators: %d: HTTP status code. */
			__( 'Paperless Customs upload failed (HTTP %d).', 'dhl-for-woocommerce' ),
			$code
		);
		if ( '' !== $detail ) {
			$message .= ' ' . $detail;
		}

		PR_DHL()->log_msg( $message );

		throw new Exception( esc_html( $message ) );
	}

	/**
	 * If eligible, generate + upload the commercial invoice and attach the batchId.
	 *
	 * On any failure, logs, adds an order note, and returns without attaching,
	 * the label is still created with physical customs docs. Never throws.
	 *
	 * @since [*next-version*]
	 *
	 * @param Item_Info $item_info The shipment item info; its services are modified in place.
	 * @param array     $args      The label arguments for the order.
	 * @return void
	 */
	public function maybe_apply_paperless_customs( Item_Info $item_info, array $args ) {
		try {
			$enabled = ( 'yes' === ( $args['order_details']['paperless_customs'] ?? '' ) );
			if ( ! $enabled ) {
				return;
			}

			$country  = $args['shipping_address']['country'] ?? '';
			$product  = $item_info->shipment['product'] ?? '';
			$pddp     = ( 'yes' === ( $args['order_details']['PDDP'] ?? '' ) );
			$order_id = $args['order_details']['order_id'] ?? 0;

			if ( ! \PR\DHL\Utils\API_Utils::is_paperless_customs_eligible( $country, $product, $pddp ) ) {
				// Only note the PDDP conflict; other ineligible cases are silent.
				if ( $pddp && 'CH' === $country && 'V53WPAK' === $product ) {
					$this->paperless_fallback_note( $order_id, __( 'Paperless Customs skipped: Prepaid Duties (PDDP) is enabled and cannot be combined with Paperless Customs. Attach physical customs documents to the parcel.', 'dhl-for-woocommerce' ) );
				}
				return;
			}

			if ( $this->paperless_unavailable ) {
				$this->paperless_fallback_note( $order_id, __( 'Paperless Customs skipped: the DHL customs service was unavailable. Attach physical customs documents to the parcel.', 'dhl-for-woocommerce' ) );
				return;
			}

			$settings = $args['dhl_settings'] ?? array();

			if ( empty( $settings['paperless_signature'] ) ) {
				$this->paperless_fallback_note( $order_id, __( 'Paperless Customs skipped: no signature image is configured. Attach physical customs documents to the parcel.', 'dhl-for-woocommerce' ) );
				return;
			}

			if ( 'yes' === ( $args['order_details']['multi_packages_enabled'] ?? '' ) ) {
				$this->paperless_fallback_note( $order_id, __( 'Paperless Customs skipped: it is not supported for multi-package shipments. Attach physical customs documents to the parcel.', 'dhl-for-woocommerce' ) );
				return;
			}

			try {
				$pdf = ( new \PR\DHL\REST_API\Parcel_DE\Commercial_Invoice_PDF() )->generate( $item_info, $settings );
			} catch ( \Throwable $e ) {
				PR_DHL()->log_msg( 'Paperless Customs invoice generation failed: ' . $e->getMessage() );
				$this->paperless_fallback_note( $order_id, __( 'Paperless Customs skipped: the commercial invoice could not be generated. Attach physical customs documents to the parcel.', 'dhl-for-woocommerce' ) );
				return;
			}

			if ( strlen( $pdf ) > 1000000 ) {
				PR_DHL()->log_msg( 'Paperless Customs invoice exceeds 1 MB: ' . strlen( $pdf ) . ' bytes' );
				$this->paperless_fallback_note( $order_id, __( 'Paperless Customs skipped: the generated invoice exceeds the 1 MB limit of DHL. Reduce the signature or logo image size and attach physical customs documents to the parcel.', 'dhl-for-woocommerce' ) );
				return;
			}

			try {
				$filename = 'invoice-' . $order_id . '.pdf';
				$batch_id = $this->upload_customs_documents( $pdf, $filename, (string) $order_id );

				// Pass-through in services_mappimng() turns this into services.paperlessCustoms = "<uuid>".
				$item_info->services['paperlessCustoms'] = $batch_id;
			} catch ( \Throwable $e ) {
				PR_DHL()->log_msg( 'Paperless Customs invoice upload failed: ' . $e->getMessage() );
				$this->paperless_fallback_note( $order_id, __( 'Paperless Customs skipped: the invoice upload to DHL failed. Attach physical customs documents to the parcel.', 'dhl-for-woocommerce' ) );
			}
		} catch ( \Throwable $e ) {
			// Never let Paperless Customs abort label creation (or a bulk run).
			PR_DHL()->log_msg( 'Paperless Customs unexpected failure: ' . $e->getMessage() );
		}
	}

	/**
	 * Record a Paperless Customs fallback on the order.
	 *
	 * @since [*next-version*]
	 *
	 * @param int    $order_id The order ID.
	 * @param string $note     Complete, translated sentence explaining the fallback.
	 * @return void
	 */
	protected function paperless_fallback_note( $order_id, $note ) {
		try {
			if ( $order_id && function_exists( 'wc_get_order' ) ) {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					$order->add_order_note( $note );
				}
			}
		} catch ( \Throwable $e ) {
			PR_DHL()->log_msg( 'Paperless Customs fallback note failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Whether an error text refers to Paperless Customs (the service, batch or customs document).
	 *
	 * @since [*next-version*]
	 *
	 * @param string $text Error message(s).
	 * @return bool
	 */
	public function is_paperless_error( $text ) {
		return (bool) preg_match( '/paperless|batch|customs-document/i', (string) $text );
	}

	/**
	 * Remove the Paperless Customs service from an item and note it on the order.
	 *
	 * @since [*next-version*]
	 *
	 * @param Item_Info $item_info The shipment item info.
	 * @return void
	 */
	public function strip_paperless_customs( Item_Info $item_info ) {
		unset( $item_info->services['paperlessCustoms'] );
		$this->paperless_fallback_note(
			$item_info->args['order_details']['order_id'] ?? 0,
			__( 'Paperless Customs skipped: DHL rejected the order with Paperless Customs, so the label was created without it. Attach physical customs documents to the parcel.', 'dhl-for-woocommerce' )
		);
	}

	/**
	 * Create the label items; if DHL fails because of Paperless Customs, retry once without it.
	 *
	 * Unrelated failures (auth, timeout, 5xx) are returned or thrown unchanged.
	 *
	 * @since [*next-version*]
	 *
	 * @param Item_Info $item_info The shipment item info.
	 * @return array The create_items() response.
	 * @throws Exception On a failure that is not Paperless Customs related, or when the retry fails.
	 */
	private function create_label_items_with_paperless_fallback( Item_Info $item_info ) {
		$has_paperless = ! empty( $item_info->services['paperlessCustoms'] );

		try {
			$response = $this->api_client->create_items( array( $item_info ) );
		} catch ( Exception $e ) {
			if ( ! $has_paperless || ! $this->is_paperless_error( $e->getMessage() ) ) {
				throw $e;
			}
			PR_DHL()->log_msg( 'Paperless Customs order rejected, retrying without it: ' . $e->getMessage() );
			$this->strip_paperless_customs( $item_info );
			return $this->api_client->create_items( array( $item_info ) );
		}

		if ( $has_paperless && ! empty( $response['errors'] ) ) {
			$text = '';
			foreach ( $response['errors'] as $error ) {
				$text .= ' ' . ( $error['message'] ?? '' );
			}
			if ( $this->is_paperless_error( $text ) ) {
				PR_DHL()->log_msg( 'Paperless Customs order rejected (207), retrying without it: ' . $text );
				$this->strip_paperless_customs( $item_info );
				return $this->api_client->create_items( array( $item_info ) );
			}
		}

		return $response;
	}

	/**
	 * Send the upload, retrying on HTTP 429 with a short backoff.
	 *
	 * Connectivity errors and 5xx responses trip the circuit breaker so that later
	 * uploads in the same request are skipped. Once a 429 has been seen, later uploads
	 * make a single attempt without sleeping, so bulk runs cannot accumulate backoff.
	 *
	 * @since [*next-version*]
	 *
	 * @param \PR\DHL\REST_API\Drivers\WP_API_Driver $driver  The raw HTTP driver.
	 * @param \PR\DHL\REST_API\Request               $request The authorized upload request.
	 * @return \PR\DHL\REST_API\Response The last response received.
	 * @throws \RuntimeException On a connectivity error.
	 */
	protected function upload_with_retry( $driver, $request ) {
		if ( $this->paperless_rate_limited ) {
			return $this->send_upload( $driver, $request );
		}

		/**
		 * Filters the maximum number of Paperless Customs upload attempts.
		 *
		 * @since [*next-version*]
		 *
		 * @param int $max_attempts Maximum attempts, including the first. Default 2.
		 */
		$max_attempts = (int) apply_filters( 'pr_dhl_paperless_upload_max_attempts', 2 );

		for ( $attempt = 1; $attempt <= $max_attempts; $attempt++ ) {
			$response = $this->send_upload( $driver, $request );
			if ( 429 !== (int) $response->status ) {
				return $response;
			}

			$this->paperless_rate_limited = true;

			if ( $attempt === $max_attempts ) {
				return $response;
			}

			/**
			 * Filters the seconds to wait before retrying a rate-limited Paperless Customs upload.
			 *
			 * @since [*next-version*]
			 *
			 * @param int $seconds Backoff in seconds. Default 2.
			 */
			$backoff = (int) apply_filters( 'pr_dhl_paperless_upload_backoff_seconds', 2 );

			/**
			 * Filters the longest Retry-After (seconds) that is honored; a longer one skips the retry.
			 *
			 * @since [*next-version*]
			 *
			 * @param int $cap Hard cap in seconds. Default 10.
			 */
			$cap = (int) apply_filters( 'pr_dhl_paperless_upload_retry_after_cap', 10 );

			if ( is_array( $response->headers ) ) {
				foreach ( $response->headers as $name => $value ) {
					if ( 'retry-after' === strtolower( (string) $name ) && is_numeric( $value ) ) {
						if ( (int) $value > $cap ) {
							return $response;
						}
						$backoff = (int) $value;
						break;
					}
				}
			}

			sleep( max( 0, min( $backoff, $cap ) ) );
		}

		return $response;
	}

	/**
	 * Send one upload request and trip the circuit breaker on connectivity errors and 5xx.
	 *
	 * @since [*next-version*]
	 *
	 * @param \PR\DHL\REST_API\Drivers\WP_API_Driver $driver  The raw HTTP driver.
	 * @param \PR\DHL\REST_API\Request               $request The authorized upload request.
	 * @return \PR\DHL\REST_API\Response
	 * @throws \RuntimeException On a connectivity error.
	 */
	private function send_upload( $driver, $request ) {
		try {
			$response = $driver->send( $request );
		} catch ( \RuntimeException $e ) {
			$this->paperless_unavailable = true;
			throw $e;
		}

		if ( (int) $response->status >= 500 ) {
			$this->paperless_unavailable = true;
		}

		return $response;
	}

	/**
	 * Route for the customs-documents upload endpoint.
	 *
	 * @since [*next-version*]
	 *
	 * @return string The route, relative to the API base URL.
	 */
	protected function request_customs_documents_route() {
		return 'v2/customs-documents';
	}

	/**
	 * Get label.
	 *
	 * @param $args
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_dhl_label( $args ) {
		$this->set_arguments( $args );
		$uom = get_option( 'woocommerce_weight_unit' );
		try {
			$item_info = new Item_Info( $args, $uom );
		} catch ( Exception $e ) {
			throw $e;
		}

		$this->maybe_apply_paperless_customs( $item_info, $args );

		// Create the item and get the barcode, retrying once without Paperless Customs if it caused the failure.
		$item_response = $this->create_label_items_with_paperless_fallback( $item_info );

		if ( ! empty( $item_response['errors'] ) ) {
			$message = '';
			foreach ( $item_response['errors'] as $error ) {
				$message .= '<br>' . $error['message'];
			}

			throw new Exception( wp_kses_post( $message ) );
		}

		if ( count( $item_response['items'] ) > 1 ) {
			$label_tracking_info = $this->save_multiple_shipments( 'label', $args['order_details']['order_id'], $item_response['items'] );
		} else {
			$label_tracking_info = $this->save_export_files( 'label', $args['order_details']['order_id'], $item_response['items'][0] );
		}

		if ( ! empty( $item_response['warnings'] ) ) {
			$label_tracking_info['dhl_label_warnings'] = $item_response['warnings'];
		}

		return $label_tracking_info;
	}

	/**
	 * Delete label.
	 *
	 * @param $label_info.
	 *
	 * @throws Exception.
	 */
	public function delete_dhl_label( $label_info ) {
		$this->api_client->delete_item( $label_info['tracking_number'] );

		if ( ! isset( $label_info['label_path'] ) ) {
			throw new Exception( esc_html__( 'DHL Label has no path!', 'dhl-for-woocommerce' ) );
		}

		$label_path = PR_DHL()->resolve_label_file_path( $label_info['label_path'] );

		// Remove the separate return label file too, if one was saved.
		if ( ! empty( $label_info['return_label_path'] ) ) {
			$return_label_path = PR_DHL()->resolve_label_file_path( $label_info['return_label_path'] );
			if ( file_exists( $return_label_path ) ) {
				wp_delete_file( $return_label_path );
			}
		}

		if ( file_exists( $label_path ) ) {
			if ( ! is_writable( $label_path ) ) {
				throw new Exception( esc_html__( 'DHL Label file is not writable!', 'dhl-for-woocommerce' ) );
			}
			wp_delete_file( $label_path );
		} else {
			throw new Exception( esc_html__( 'DHL Label could not be deleted!', 'dhl-for-woocommerce' ) );
		}
	}

	/**
	 * Save label file.
	 *
	 * @param $prefix
	 * @param $order_id
	 * @param $label_data
	 *
	 * @return array
	 * @throws Exception
	 */
	public function save_data_file( $prefix, $order_id, $label_data ) {
		$data_name = 'dhl-' . $prefix . '-' . $order_id . '.pdf';
		$data_path = PR_DHL()->get_dhl_label_folder_dir() . $data_name;
		$data_url  = PR_DHL()->get_dhl_label_folder_url() . $data_name;

		// windows path will not get exception
		if ( validate_file( $data_path ) > 0 && validate_file( $data_path ) !== 2 ) {
			throw new Exception( esc_html__( 'Invalid file path!', 'dhl-for-woocommerce' ) );
		}

		$label_data_decoded = base64_decode( $label_data );
		$file_ret           = file_put_contents( $data_path, $label_data_decoded );

		if ( empty( $file_ret ) ) {
			throw new Exception( esc_html__( 'DHL label file cannot be saved!', 'dhl-for-woocommerce' ) );
		}

		return array(
			'label_url'  => $data_url,
			'label_path' => $data_path,
		);
	}

	/**
	 * Merge export files.
	 *
	 * @throws exception
	 */
	public function save_export_files( $prefix, $order_id, $item ) {

		$file = $this->save_data_file( $prefix, $order_id, $item->label->b64 );

		// Merge export doc with label
		if ( isset( $item->customsDoc->b64 ) ) {
			$loader    = PR_DHL_Libraryloader::instance();
			$pdfMerger = $loader->get_pdf_merger();

			if ( $pdfMerger ) {
				$pdfMerger->addPDF( $file['label_path'] );

				$file = $this->save_data_file( $prefix . '-customs', $order_id, $item->customsDoc->b64 );
				$pdfMerger->addPDF( $file['label_path'] );

				$filename   = 'dhl-' . $prefix . '-' . $order_id . '-export' . '.pdf';
				$label_url  = PR_DHL()->get_dhl_label_folder_url() . $filename;
				$label_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
				$pdfMerger->merge( 'file', $label_path );
			} else {
				throw new Exception( 'Failed to merge export files.' );
			}
		} else {
			$label_url  = $file['label_url'];
			$label_path = $file['label_path'];
		}

		// If return label exists, add number
		$return_num = '';
		if ( isset( $item->returnShipmentNo ) ) {
			$return_num = $item->returnShipmentNo;
		}

		// When "combine=false" is sent, DHL returns the return label as its own file. Save it separately.
		$return_label_url  = '';
		$return_label_path = '';
		if ( isset( $item->returnLabel->b64 ) ) {
			$return_file       = $this->save_data_file( $prefix . '-return', $order_id, $item->returnLabel->b64 );
			$return_label_url  = $return_file['label_url'];
			$return_label_path = $return_file['label_path'];
		}

		return array(
			'label_url'           => $label_url,
			'label_path'          => $label_path,
			'tracking_number'     => $item->shipmentNo,
			'return_label_number' => $return_num,
			'return_label_url'    => $return_label_url,
			'return_label_path'   => $return_label_path,
		);
	}

	/**
	 * Merge returned labels.
	 *
	 * @throws exception
	 */
	public function save_multiple_shipments( $prefix, $order_id, $items ) {
		// Merge PDF files
		$loader    = PR_DHL_Libraryloader::instance();
		$pdfMerger = $loader->get_pdf_merger();

		$shipmentNos         = array();
		$return_nums         = array();
		$return_label_files  = array();

		if ( $pdfMerger ) {
			foreach ( $items as $key => $item ) {
				$file = $this->save_export_files( $prefix . '-' . $key, $order_id, $item );

				$pdfMerger->addPDF( $file['label_path'] );

				array_push( $shipmentNos, $file['tracking_number'] );
				array_push( $return_nums, $file['return_label_number'] );

				if ( ! empty( $file['return_label_path'] ) ) {
					$return_label_files[] = $file['return_label_path'];
				}
			}

			$filename   = 'dhl-' . $prefix . '-' . $order_id . '-multiple' . '.pdf';
			$label_url  = PR_DHL()->get_dhl_label_folder_url() . $filename;
			$label_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
			$pdfMerger->merge( 'file', $label_path );
		} else {
			throw new Exception( 'Failed to merge multiple files.' );
		}

		// Merge the separate return labels (combine=false) into a single return PDF.
		$return_label_url  = '';
		$return_label_path = '';
		if ( ! empty( $return_label_files ) ) {
			$return_merger = $loader->get_pdf_merger();

			if ( $return_merger ) {
				foreach ( $return_label_files as $return_file ) {
					$return_merger->addPDF( $return_file );
				}

				$return_filename   = 'dhl-' . $prefix . '-' . $order_id . '-return-multiple' . '.pdf';
				$return_label_url  = PR_DHL()->get_dhl_label_folder_url() . $return_filename;
				$return_label_path = PR_DHL()->get_dhl_label_folder_dir() . $return_filename;
				$return_merger->merge( 'file', $return_label_path );
			}
		}

		return array(
			'label_url'           => $label_url,
			'label_path'          => $label_path,
			'tracking_number'     => $shipmentNos,
			'return_label_number' => $return_nums,
			'return_label_url'    => $return_label_url,
			'return_label_path'   => $return_label_path,
		);
	}

	/**
	 * Set request args.
	 *
	 * @param $args
	 *
	 * @return void
	 * @throws Exception
	 */
	public function set_arguments( $args ) {
		// Validate set args

		if ( empty( $args['dhl_settings']['api_user'] ) ) {
			throw new Exception( esc_html__( 'Please, provide the username in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['api_pwd'] ) ) {
			throw new Exception( esc_html__( 'Please, provide the password for the username in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		// Validate order details
		if ( empty( $args['dhl_settings']['account_num'] ) ) {
			throw new Exception( esc_html__( 'Please, provide an account in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['participation'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a participation number for the shipping method in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['shipper_name'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a shipper name in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['shipper_address'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a shipper address in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['shipper_address_no'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a shipper address number in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['shipper_address_city'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a shipper city in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['dhl_settings']['shipper_address_zip'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a shipper postcode in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		if ( $args['dhl_settings']['add_logo'] == 'yes' && empty( $args['dhl_settings']['shipper_reference'] ) ) {
			throw new Exception( esc_html__( 'Please, provide a shipper reference in the DHL shipping settings', 'dhl-for-woocommerce' ) );
		}

		// Order details
		if ( empty( $args['order_details']['dhl_product'] ) ) {
			throw new Exception( esc_html__( 'DHL "Product" is empty!', 'dhl-for-woocommerce' ) );
		}

		// return receiver
		if ( isset( $args['order_details']['return_address'] ) && ( $args['order_details']['return_address'] == 'yes' ) ) {

			if ( ( $args['order_details']['dhl_product'] != 'V01PAK' ) && ( $args['order_details']['dhl_product'] != 'V01PRIO' ) && ( $args['order_details']['dhl_product'] != 'V86PARCEL' ) && ( $args['order_details']['dhl_product'] != 'V55PAK' ) ) {
				throw new Exception( esc_html__( 'Returns are not supported by this DHL Service.', 'dhl-for-woocommerce' ) );
			}

			if ( empty( $args['dhl_settings']['return_name'] ) ) {
				throw new Exception( esc_html__( 'Please, provide a return name in the DHL shipping settings', 'dhl-for-woocommerce' ) );
			}

			if ( empty( $args['dhl_settings']['return_address'] ) ) {
				throw new Exception( esc_html__( 'Please, provide a return address in the DHL shipping settings', 'dhl-for-woocommerce' ) );
			}

			if ( empty( $args['dhl_settings']['return_address_no'] ) ) {
				throw new Exception( esc_html__( 'Please, provide a return address number in the DHL shipping settings', 'dhl-for-woocommerce' ) );
			}

			if ( empty( $args['dhl_settings']['return_address_city'] ) ) {
				throw new Exception( esc_html__( 'Please, provide a return city in the DHL shipping settings', 'dhl-for-woocommerce' ) );
			}

			if ( empty( $args['dhl_settings']['return_address_zip'] ) ) {
				throw new Exception( esc_html__( 'Please, provide a return postcode in the DHL shipping settings', 'dhl-for-woocommerce' ) );
			}
		}

		if ( empty( $args['order_details']['order_id'] ) ) {
			throw new Exception( esc_html__( 'Shop "Order ID" is empty!', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['order_details']['weightUom'] ) ) {
			throw new Exception( esc_html__( 'Shop "Weight Units of Measure" is empty!', 'dhl-for-woocommerce' ) );
		}

		if ( isset( $args['order_details']['identcheck'] ) && ( $args['order_details']['identcheck'] == 'yes' ) ) {
			if ( empty( $args['shipping_address']['first_name'] ) || empty( $args['shipping_address']['last_name'] ) ) {
				throw new Exception( esc_html__( 'First name and last name must be passed for "Identity Check".', 'dhl-for-woocommerce' ) );
			}

			if ( empty( $args['order_details']['identcheck_dob'] ) && empty( $args['order_details']['identcheck_age'] ) ) {
				throw new Exception( esc_html__( 'Either a "Date of Birth" or "Minimum Age" must be eneted for "Ident-Check".', 'dhl-for-woocommerce' ) );
			}
		}

		// Validate weight
		try {
			$this->validate_field( 'weight', $args['order_details']['weight'] );
		} catch ( Exception $e ) {
			throw new Exception( 'Weight - ' . esc_html( $e->getMessage() ) );
		}

		if ( isset( $args['order_details']['multi_packages_enabled'] ) && ( $args['order_details']['multi_packages_enabled'] == 'yes' ) ) {

			if ( isset( $args['order_details']['total_packages'] ) ) {

				for ( $i = 0; $i < intval( $args['order_details']['total_packages'] ); $i++ ) {

					if ( empty( $args['order_details']['packages_number'][ $i ] ) ) {
						throw new Exception( esc_html__( ' A package number is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce' ) );
					}

					if ( empty( $args['order_details']['packages_weight'][ $i ] ) ) {
						throw new Exception( esc_html__( 'A package weight is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce' ) );
					}

					if ( empty( $args['order_details']['packages_length'][ $i ] ) ) {
						throw new Exception( esc_html__( 'A package length is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce' ) );
					}

					if ( empty( $args['order_details']['packages_width'][ $i ] ) ) {
						throw new Exception( esc_html__( 'A package width is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce' ) );
					}

					if ( empty( $args['order_details']['packages_height'][ $i ] ) ) {
						throw new Exception( esc_html__( 'A package height is empty. Ensure all package details are filled in.', 'dhl-for-woocommerce' ) );
					}
				}
			}
		} elseif ( empty( $args['order_details']['weight'] ) ) {

				throw new Exception( esc_html__( 'Order "Weight" is empty!', 'dhl-for-woocommerce' ) );
		}

		// if ( empty( $args['order_details']['duties'] )) {
		// throw new Exception( esc_html__('DHL "Duties" is empty!', 'dhl-for-woocommerce') );
		// }

		if ( empty( $args['order_details']['currency'] ) ) {
			throw new Exception( esc_html__( 'Shop "Currency" is empty!', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['shipping_address']['city'] ) ) {
			throw new Exception( esc_html__( 'Shipping "City" is empty!', 'dhl-for-woocommerce' ) );
		}

		if ( empty( $args['shipping_address']['country'] ) ) {
			throw new Exception( esc_html__( 'Shipping "Country" is empty!', 'dhl-for-woocommerce' ) );
		}

		// Validate shipping address
		if ( empty( $args['shipping_address']['address_1'] ) ) {
			throw new Exception( esc_html__( 'Shipping "Address 1" is empty!', 'dhl-for-woocommerce' ) );
		}

		$pos_ps = PR_DHL()->is_packstation( $args['shipping_address']['address_1'] );
		$pos_rs = PR_DHL()->is_parcelshop( $args['shipping_address']['address_1'] );
		$pos_po = PR_DHL()->is_post_office( $args['shipping_address']['address_1'] );

		// If Packstation, post number is mandatory
		if ( $pos_ps && empty( $args['shipping_address']['dhl_postnum'] ) ) {
			throw new Exception( esc_html__( 'Post Number is missing, it is mandatory for "Packstation" delivery.', 'dhl-for-woocommerce' ) );
		}

		// Check address 2 if no parcel shop is being selected
		if ( ! $pos_ps && ! $pos_rs && ! $pos_po ) {
			// If address 2 missing, set last piece of an address to be address 2
			if ( empty( $args['shipping_address']['address_2'] ) ) {
				$set_key = false;
				// Break address into pieces by spaces
				$address_exploded = explode( ' ', $args['shipping_address']['address_1'] );

				// If no spaces found
				if ( count( $address_exploded ) == 1 ) {
					// Break address into pieces by '.'
					$address_exploded = explode( '.', $args['shipping_address']['address_1'] );

					// If no address number and in Germany, return error
					if ( 1 === count( $address_exploded ) && 'DE' === $args['shipping_address']['country'] ) {
						throw new Exception( esc_html__( 'Shipping street number is missing!', 'dhl-for-woocommerce' ) );
					}
				}

				// If greater than 1, means there are two parts to the address...otherwise Address 2 is empty which is possible in some countries outside of Germany
				if ( count( $address_exploded ) > 1 ) {
					// Loop through address and set number value only...
					// ...last found number will be 'address_2'
					foreach ( $address_exploded as $address_key => $address_value ) {
						if ( is_numeric( $address_value ) ) {
							// Set last index as street number
							$set_key = $address_key;
						}
					}

					// If no number was found, then take last part of address no matter what it is
					if ( false === $set_key ) {
						$set_key = $address_key;
					}

					// The number is the first part of address 1
					if ( 0 === $set_key ) {
						// Set "address_2" first, as first part
						$args['shipping_address']['address_2'] = implode( ' ', array_slice( $address_exploded, 0, 1 ) );
						// Remove "address_2" from "address_1"
						$args['shipping_address']['address_1'] = implode( ' ', array_slice( $address_exploded, 1 ) );
					} else {
						// Set "address_2" first
						$args['shipping_address']['address_2'] = implode( ' ', array_slice( $address_exploded, $set_key ) );
						// Remove "address_2" from "address_1"
						$args['shipping_address']['address_1'] = implode( ' ', array_slice( $address_exploded, 0, $set_key ) );
					}
				}
			}
		}

		// Add default values for required fields that might not be passed e.g. phone
		$default_args = array(
			'shipping_address' =>
										array(
											'name'      => '',
											'company'   => '',
											'address_2' => '',
											'email'     => '',
											'postcode'  => '',
											'state'     => '',
											'phone'     => ' ',
										),
		);

		$args['shipping_address'] = wp_parse_args( $args['shipping_address'], $default_args['shipping_address'] );

		$default_args_item = array(
			'item_description' => '',
			'sku'              => '',
			'line_total'       => 0,
			'country_origin'   => '',
			'hs_code'          => '',
			'qty'              => 1,
		);

		foreach ( $args['items'] as $key => $item ) {

			if ( ! empty( $item['hs_code'] ) ) {
				try {
					$this->validate_field( 'hs_code', $item['hs_code'] );
				} catch ( Exception $e ) {
					throw new Exception( 'HS Code - ' . esc_html( $e->getMessage() ) );
				}
			}

			$args['items'][ $key ] = wp_parse_args( $item, $default_args_item );
		}
	}

	/**
	 * Validate field.
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function validate_field( $key, $value ) {

		try {

			switch ( $key ) {
				case 'weight':
					$this->validate( wc_format_decimal( $value ) );
					break;
				case 'hs_code':
					$this->validate( $value, 'string', 4, 11 );
					break;
				case 'pickup':
					$this->validate( $value, 'string', 14, 14 );
					break;
				case 'distribution':
					$this->validate( $value, 'string', 6, 6 );
					break;
			}
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Validate value.
	 *
	 * @param $value
	 * @param $type
	 * @param $min_len
	 * @param $max_len
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function validate( $value, $type = 'int', $min_len = 0, $max_len = 0 ) {

		switch ( $type ) {
			case 'string':
				if ( ( strlen( $value ) < $min_len ) || ( strlen( $value ) > $max_len ) ) {
					if ( $min_len == $max_len ) {
						/* translators: %s is the required number of characters */
						throw new Exception( sprintf( esc_html__( 'The value must be %s characters.', 'dhl-for-woocommerce' ), esc_attr( $min_len ) ) );
					} else {
						/* translators: %1$s is the minimum number of characters, %2$s is the maximum number of characters */
						throw new Exception( sprintf( esc_html__( 'The value must be between %1$s and %2$s characters.', 'dhl-for-woocommerce' ), esc_attr( $min_len ), esc_attr( $max_len ) ) );
					}
				}
				break;
			case 'int':
				if ( ! is_numeric( $value ) ) {
					throw new Exception( esc_html__( 'The value must be a number', 'dhl-for-woocommerce' ) );
				}
				break;
		}
	}
}
