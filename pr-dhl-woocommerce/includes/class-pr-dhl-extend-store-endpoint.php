<?php

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Extend store endpont.
 *
 * @package  Extend_Store_Endpoint
 * @category Shipping
 */
if ( ! class_exists( 'PR_DHL_Extend_Store_Endpoint' ) ) :

	/**
	 * DHL checkout Extend Store API.
	 */
	class PR_DHL_Extend_Store_Endpoint {
		/**
		 * Stores Rest Extending instance.
		 *
		 * @var ExtendRestApi
		 */
		private static $extend;

		/**
		 * Plugin Identifier, unique to each plugin.
		 *
		 * @var string
		 */
		const IDENTIFIER = 'pr-dhl';

		/**
		 * Bootstraps the class and hooks required data.
		 *
		 */
		public static function init() {
			self::$extend = Automattic\WooCommerce\StoreApi\StoreApi::container()->get( \Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema::class );
			self::extend_store();
		}

		/**
		 * Registers the actual data into each endpoint.
		 */
		public static function extend_store() {

			if ( is_callable( [ self::$extend, 'register_endpoint_data' ] ) ) {
				self::$extend->register_endpoint_data(
					[
						'endpoint'        => CheckoutSchema::IDENTIFIER,
						'namespace'       => self::IDENTIFIER,
						'schema_callback' => [ 'PR_DHL_Extend_Store_Endpoint', 'extend_checkout_schema' ],
						'schema_type'     => ARRAY_A,
					]
				);
			}
		}

		/**
		 * Register DHL checkout schema into the Checkout endpoint.
		 *
		 * @return array Registered schema.
		 *
		 */
		public static function extend_checkout_schema() {
			return [
				'preferredDay'              => [
					'description' => 'Preferred delivery day for the shipment',
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
					'arg_options' => [
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					]
				],
				'preferredLocationNeighbor' => [
					'description' => 'Preferred location or neighbor for delivery',
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
					'arg_options' => [
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					]
				],
				'preferredLocation'         => [
					'description' => 'Preferred drop-off location for the shipment',
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
					'arg_options' => [
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					]
				],
				'preferredNeighborName'     => [
					'description' => 'Name of the preferred neighbor for delivery',
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
					'arg_options' => [
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					]
				],
				'preferredNeighborAddress'  => [
					'description' => 'Address of the preferred neighbor for delivery',
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
					'arg_options' => [
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					]
				],
				'addressType'  => [
					'description' => 'Address Type',
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
					'arg_options' => [
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					]
				],
				'postNumber'  => [
					'description' => 'Post Number',
					'type'        => 'string',
					'context'     => [ 'view', 'edit' ],
					'readonly'    => true,
					'arg_options' => [
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
					]
				],
			];
		}

	}

endif;
