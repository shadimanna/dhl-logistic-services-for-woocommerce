<?php

namespace PR\DHL\REST_API\Parcel_DE;

use Exception;
use PR\DHL\Utils\Args_Parser;

/**
 * A class that represents a Deutsche Post item, which corresponds to a WooCommerce order.
 *
 * @since [*next-version*]
 */
class Item_Info {
    /**
     * Shipment details.
     *
     * @since [*next-version*]
     *
     * @var array
     */
    public $shipment;

	/**
	 * Shipper information, including contact information, address. Alternatively, a predefined shipper reference can be used.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $shipper;

	/**
	 * Consignee address information. Either a doorstep address (contact address) including contact information or a droppoint address.
	 *
	 * @since [*next-version*]
	 *
	 * @var array
	 */
	public $consignee;

	/**
	 * Details for the shipment, such as dimensions, content
     *
	 * @since [*next-version*]
	 *
	 * @var array[]
	 */
	public $details;

    /**
     * For international shipments, this array contains information necessary for customs about the exported goods.
     *
     * @var array
     */
    public $services;

    /**
     * For international shipments, this array contains information necessary for customs about the exported goods.
     *
     * @var array
     */
    public $customs;

	/**
	 * The units of measurement used for weights in the input args.
	 *
	 * @since [*next-version*]
	 *
	 * @var string
	 */
	protected $weightUom;

	/**
	 * Constructor.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 * @param string $weightUom The units of measurement used for weights in the input args.
	 *
	 * @throws Exception If some data in $args did not pass validation.
	 */
	public function __construct( $args, $weightUom = 'g' ) {
		$this->weightUom = $weightUom;
		$this->parse_args( $args, $weightUom );
	}

	/**
	 * Parses the arguments and sets the instance's properties.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $args The arguments to parse.
	 *
	 * @throws Exception If some data in $args did not pass validation.
	 */
	protected function parse_args( $args ) {
		$settings       = $args[ 'dhl_settings' ];
		$recipient_info = $args[ 'shipping_address' ] + $settings;
		$shipping_info  = $args[ 'order_details' ] + $settings;
		$items_info     = $args['items'];

		$this->shipment = Args_Parser::parse_args( $shipping_info, $this->get_shipment_info_schema() );
        $this->shipper  = Args_Parser::parse_args( $settings, $this->get_shipper_info_schema() );

		$this->details = array();
		foreach ( $items_info as $item_info ) {
			$this->details[] = Args_Parser::parse_args( $item_info, $this->get_content_item_info_schema() );
		}
	}

	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing shipment info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_shipment_info_schema() {
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
            'dhl_product'                   => array(
                'rename' => 'product',
                'error'  => __( 'DHL "Product" is empty!', 'dhl-for-woocommerce' ),
                'sanitize' => function ( $product ) use ($self) {

                    $product_info 	= explode( '-', $product );
                    $product 		= $product_info[0];

                    return $product;
                },
            ),
			'dhl_label_ref'                 => array(
				'rename'    => 'refNo',
				'sanitize'  => function( $label_ref ) use ($self) {
					return $self->string_length_sanitization( $label_ref, 50 );
				}
			),
            'dhl_pickup_billing_number'     => array(
                'rename' => 'billingNumber',
                'sanitize' => function( $account ) use ($self) {

                    if ( empty( $account ) ) {
                        throw new Exception(
                            __( 'Check your settings "Account Number" and "Participation Number".', 'dhl-for-woocommerce' )
                        );
                    }

                    return $account;
                }
            ),
            'dhl_cost_center'               => array(
                'rename' => 'costCenter',
                'default' => ''
            )
		);
	}

    /**
     * Retrieves the args scheme to use with {@link Args_Parser} for parsing shipper info.
     *
     * @since [*next-version*]
     *
     * @return array
     */
    protected function get_shipper_info_schema() {
        // Closures in PHP 5.3 do not inherit class context
        // So we need to copy $this into a lexical variable and pass it to closures manually
        $self = $this;

        return array(
            'shipper_name'          => array(
                'rename'    => 'name1',
                'sanitize'  => function( $name ) use ($self) {
                    if ( empty( $name ) ) {
                        throw new Exception(
                            __( '"Account Name" in settings is empty.', 'dhl-for-woocommerce' )
                        );
                    }
                    return $self->string_length_sanitization( $name, 50 );
                }
            ),
            'shipper_phone'         => array(
                'rename' => 'phone',
                'default' => '',
            ),
            'shipper_email'         => array(
                'rename' => 'email',
                'default' => '',
            ),
            'shipper_address'       => array(
                'rename' => 'addressStreet',
                'error' => __( 'Shipper "Address 1" is empty!', 'dhl-for-woocommerce' ),
                'sanitize' => function( $name ) use ($self) {

                    if (empty($name)) {
                        throw new Exception(
                            __( 'Shipper "Address 1" is empty!', 'dhl-for-woocommerce' )
                        );
                    }

                    return $self->string_length_sanitization( $name, 50 );
                }
            ),
            'shipper_address_no'    => array(
                'rename' => 'addressHouse',
                'default' => '',
            ),
            'shipper_address_zip'   => array(
                'rename' => 'postalCode',
                'error' => __( 'Shipper "Postcode" is empty!', 'dhl-for-woocommerce' ),
            ),
            'shipper_address_city'  => array(
                'rename' => 'city',
                'error' => __( 'Shipper "City" is empty!', 'dhl-for-woocommerce' ),
            ),
            'shipper_address_state' => array(
                'rename' => 'state',
                'default' => '',
            ),
            'shipper_country'       => array(
                'rename' => 'country',
                'sanitize'  => function( $countryCode ) use ( $self ) {
                    if ( empty( $countryCode ) ) {
                        throw new Exception(
                            __( 'Shipper "Country" is empty!', 'dhl-for-woocommerce' )
                        );
                    }
                    return $self->country_code_to_alpha3( $countryCode );
                }
            ),
        );
    }


	/**
	 * Retrieves the args scheme to use with {@link Args_Parser} for parsing order content item info.
	 *
	 * @since [*next-version*]
	 *
	 * @return array
	 */
	protected function get_content_item_info_schema()
	{
		// Closures in PHP 5.3 do not inherit class context
		// So we need to copy $this into a lexical variable and pass it to closures manually
		$self = $this;

		return array(
			'hs_code'     => array(
				'default'  => '',
				'validate' => function( $hs_code ) {
					$length = is_string( $hs_code ) ? strlen( $hs_code ) : 0;

					if (empty($length)) {
						return;
					}

					if ( $length < 4 || $length > 20 ) {
						throw new Exception(
							__( 'Item HS Code must be between 4 and 20 characters long', 'dhl-for-woocommerce' )
						);
					}
				},
			),
			'item_description' => array(
				'rename' => 'description',
				'default' => '',
				'sanitize' => function( $description ) use ($self) {

					return $self->string_length_sanitization( $description, 33 );
				}
			),
			'item_export' => array(
				'rename' => 'description_export',
				'default' => '',
				'sanitize' => function( $description_export ) use ($self) {

					return $self->string_length_sanitization( $description_export, 33 );
				}
			),
			'product_id'  => array(
				'default' => '',
			),
			'sku'         => array(
				'default' => '',
			),
			'item_value'       => array(
				'rename' => 'value',
				'default' => 0,
				'sanitize' => function( $value, $args ) use ($self) {
					
					$qty 			= isset( $args['qty'] ) && is_numeric( $args['qty'] )? floatval( $args['qty'] ) : 1;
					$total_value 	= floatval( $value ) * $qty;
					
					return (string) $self->float_round_sanitization( $total_value, 2 );
				}
			),
			'country_origin' => array(
				'rename' => 'origin',
				'default' => PR_DHL()->get_base_country(),
			),
			'qty'         => array(
				'default' => 1,
			),
			'item_weight'      => array(
				'rename' => 'weight',
				'default' => 1,
				'sanitize' => function ( $weight ) use ($self) {

					$weight = $self->maybe_convert_to_grams( $weight, $self->weightUom );

					return $weight;
				}
			),
		);
	}

	/**
	 * Converts a given weight into grams, if necessary.
	 *
	 * @since [*next-version*]
	 *
	 * @param float $weight The weight amount.
	 * @param string $uom The unit of measurement of the $weight parameter..
	 *
	 * @return float The potentially converted weight.
	 */
	protected function maybe_convert_to_grams( $weight, $uom ) {
		$weight = floatval( $weight );

		switch ( $uom ) {
			case 'kg':
				$weight = $weight * 1000;
				break;
			case 'lb':
				$weight = $weight / 2.2;
				break;
			case 'oz':
				$weight = $weight / 35.274;
				break;
		}
		
		return round( $weight );
	}

	protected function float_round_sanitization( $float, $numcomma ) {

		$float = round( floatval( $float ), $numcomma);

        return number_format($float, 2, '.', '');
	}

	protected function string_length_sanitization( $string, $max ) {

		$max = intval( $max );

		if( strlen( $string ) <= $max ){

			return $string;
		}

		return substr( $string, 0, ( $max-1 ));
	}

    protected function country_code_to_alpha3( $countryCode ) {
        $countries = array(
            'AF' => 'AFG', //Afghanistan
            'AX' => 'ALA', //&#197;land Islands
            'AL' => 'ALB', //Albania
            'DZ' => 'DZA', //Algeria
            'AS' => 'ASM', //American Samoa
            'AD' => 'AND', //Andorra
            'AO' => 'AGO', //Angola
            'AI' => 'AIA', //Anguilla
            'AQ' => 'ATA', //Antarctica
            'AG' => 'ATG', //Antigua and Barbuda
            'AR' => 'ARG', //Argentina
            'AM' => 'ARM', //Armenia
            'AW' => 'ABW', //Aruba
            'AU' => 'AUS', //Australia
            'AT' => 'AUT', //Austria
            'AZ' => 'AZE', //Azerbaijan
            'BS' => 'BHS', //Bahamas
            'BH' => 'BHR', //Bahrain
            'BD' => 'BGD', //Bangladesh
            'BB' => 'BRB', //Barbados
            'BY' => 'BLR', //Belarus
            'BE' => 'BEL', //Belgium
            'BZ' => 'BLZ', //Belize
            'BJ' => 'BEN', //Benin
            'BM' => 'BMU', //Bermuda
            'BT' => 'BTN', //Bhutan
            'BO' => 'BOL', //Bolivia
            'BQ' => 'BES', //Bonaire, Saint Estatius and Saba
            'BA' => 'BIH', //Bosnia and Herzegovina
            'BW' => 'BWA', //Botswana
            'BV' => 'BVT', //Bouvet Islands
            'BR' => 'BRA', //Brazil
            'IO' => 'IOT', //British Indian Ocean Territory
            'BN' => 'BRN', //Brunei
            'BG' => 'BGR', //Bulgaria
            'BF' => 'BFA', //Burkina Faso
            'BI' => 'BDI', //Burundi
            'KH' => 'KHM', //Cambodia
            'CM' => 'CMR', //Cameroon
            'CA' => 'CAN', //Canada
            'CV' => 'CPV', //Cape Verde
            'KY' => 'CYM', //Cayman Islands
            'CF' => 'CAF', //Central African Republic
            'TD' => 'TCD', //Chad
            'CL' => 'CHL', //Chile
            'CN' => 'CHN', //China
            'CX' => 'CXR', //Christmas Island
            'CC' => 'CCK', //Cocos (Keeling) Islands
            'CO' => 'COL', //Colombia
            'KM' => 'COM', //Comoros
            'CG' => 'COG', //Congo
            'CD' => 'COD', //Congo, Democratic Republic of the
            'CK' => 'COK', //Cook Islands
            'CR' => 'CRI', //Costa Rica
            'CI' => 'CIV', //Côte d\'Ivoire
            'HR' => 'HRV', //Croatia
            'CU' => 'CUB', //Cuba
            'CW' => 'CUW', //Curaçao
            'CY' => 'CYP', //Cyprus
            'CZ' => 'CZE', //Czech Republic
            'DK' => 'DNK', //Denmark
            'DJ' => 'DJI', //Djibouti
            'DM' => 'DMA', //Dominica
            'DO' => 'DOM', //Dominican Republic
            'EC' => 'ECU', //Ecuador
            'EG' => 'EGY', //Egypt
            'SV' => 'SLV', //El Salvador
            'GQ' => 'GNQ', //Equatorial Guinea
            'ER' => 'ERI', //Eritrea
            'EE' => 'EST', //Estonia
            'ET' => 'ETH', //Ethiopia
            'FK' => 'FLK', //Falkland Islands
            'FO' => 'FRO', //Faroe Islands
            'FJ' => 'FIJ', //Fiji
            'FI' => 'FIN', //Finland
            'FR' => 'FRA', //France
            'GF' => 'GUF', //French Guiana
            'PF' => 'PYF', //French Polynesia
            'TF' => 'ATF', //French Southern Territories
            'GA' => 'GAB', //Gabon
            'GM' => 'GMB', //Gambia
            'GE' => 'GEO', //Georgia
            'DE' => 'DEU', //Germany
            'GH' => 'GHA', //Ghana
            'GI' => 'GIB', //Gibraltar
            'GR' => 'GRC', //Greece
            'GL' => 'GRL', //Greenland
            'GD' => 'GRD', //Grenada
            'GP' => 'GLP', //Guadeloupe
            'GU' => 'GUM', //Guam
            'GT' => 'GTM', //Guatemala
            'GG' => 'GGY', //Guernsey
            'GN' => 'GIN', //Guinea
            'GW' => 'GNB', //Guinea-Bissau
            'GY' => 'GUY', //Guyana
            'HT' => 'HTI', //Haiti
            'HM' => 'HMD', //Heard Island and McDonald Islands
            'VA' => 'VAT', //Holy See (Vatican City State)
            'HN' => 'HND', //Honduras
            'HK' => 'HKG', //Hong Kong
            'HU' => 'HUN', //Hungary
            'IS' => 'ISL', //Iceland
            'IN' => 'IND', //India
            'ID' => 'IDN', //Indonesia
            'IR' => 'IRN', //Iran
            'IQ' => 'IRQ', //Iraq
            'IE' => 'IRL', //Republic of Ireland
            'IM' => 'IMN', //Isle of Man
            'IL' => 'ISR', //Israel
            'IT' => 'ITA', //Italy
            'JM' => 'JAM', //Jamaica
            'JP' => 'JPN', //Japan
            'JE' => 'JEY', //Jersey
            'JO' => 'JOR', //Jordan
            'KZ' => 'KAZ', //Kazakhstan
            'KE' => 'KEN', //Kenya
            'KI' => 'KIR', //Kiribati
            'KP' => 'PRK', //Korea, Democratic People\'s Republic of
            'KR' => 'KOR', //Korea, Republic of (South)
            'KW' => 'KWT', //Kuwait
            'KG' => 'KGZ', //Kyrgyzstan
            'LA' => 'LAO', //Laos
            'LV' => 'LVA', //Latvia
            'LB' => 'LBN', //Lebanon
            'LS' => 'LSO', //Lesotho
            'LR' => 'LBR', //Liberia
            'LY' => 'LBY', //Libya
            'LI' => 'LIE', //Liechtenstein
            'LT' => 'LTU', //Lithuania
            'LU' => 'LUX', //Luxembourg
            'MO' => 'MAC', //Macao S.A.R., China
            'MK' => 'MKD', //Macedonia
            'MG' => 'MDG', //Madagascar
            'MW' => 'MWI', //Malawi
            'MY' => 'MYS', //Malaysia
            'MV' => 'MDV', //Maldives
            'ML' => 'MLI', //Mali
            'MT' => 'MLT', //Malta
            'MH' => 'MHL', //Marshall Islands
            'MQ' => 'MTQ', //Martinique
            'MR' => 'MRT', //Mauritania
            'MU' => 'MUS', //Mauritius
            'YT' => 'MYT', //Mayotte
            'MX' => 'MEX', //Mexico
            'FM' => 'FSM', //Micronesia
            'MD' => 'MDA', //Moldova
            'MC' => 'MCO', //Monaco
            'MN' => 'MNG', //Mongolia
            'ME' => 'MNE', //Montenegro
            'MS' => 'MSR', //Montserrat
            'MA' => 'MAR', //Morocco
            'MZ' => 'MOZ', //Mozambique
            'MM' => 'MMR', //Myanmar
            'NA' => 'NAM', //Namibia
            'NR' => 'NRU', //Nauru
            'NP' => 'NPL', //Nepal
            'NL' => 'NLD', //Netherlands
            'AN' => 'ANT', //Netherlands Antilles
            'NC' => 'NCL', //New Caledonia
            'NZ' => 'NZL', //New Zealand
            'NI' => 'NIC', //Nicaragua
            'NE' => 'NER', //Niger
            'NG' => 'NGA', //Nigeria
            'NU' => 'NIU', //Niue
            'NF' => 'NFK', //Norfolk Island
            'MP' => 'MNP', //Northern Mariana Islands
            'NO' => 'NOR', //Norway
            'OM' => 'OMN', //Oman
            'PK' => 'PAK', //Pakistan
            'PW' => 'PLW', //Palau
            'PS' => 'PSE', //Palestinian Territory
            'PA' => 'PAN', //Panama
            'PG' => 'PNG', //Papua New Guinea
            'PY' => 'PRY', //Paraguay
            'PE' => 'PER', //Peru
            'PH' => 'PHL', //Philippines
            'PN' => 'PCN', //Pitcairn
            'PL' => 'POL', //Poland
            'PT' => 'PRT', //Portugal
            'PR' => 'PRI', //Puerto Rico
            'QA' => 'QAT', //Qatar
            'RE' => 'REU', //Reunion
            'RO' => 'ROU', //Romania
            'RU' => 'RUS', //Russia
            'RW' => 'RWA', //Rwanda
            'BL' => 'BLM', //Saint Barth&eacute;lemy
            'SH' => 'SHN', //Saint Helena
            'KN' => 'KNA', //Saint Kitts and Nevis
            'LC' => 'LCA', //Saint Lucia
            'MF' => 'MAF', //Saint Martin (French part)
            'SX' => 'SXM', //Sint Maarten / Saint Matin (Dutch part)
            'PM' => 'SPM', //Saint Pierre and Miquelon
            'VC' => 'VCT', //Saint Vincent and the Grenadines
            'WS' => 'WSM', //Samoa
            'SM' => 'SMR', //San Marino
            'ST' => 'STP', //S&atilde;o Tom&eacute; and Pr&iacute;ncipe
            'SA' => 'SAU', //Saudi Arabia
            'SN' => 'SEN', //Senegal
            'RS' => 'SRB', //Serbia
            'SC' => 'SYC', //Seychelles
            'SL' => 'SLE', //Sierra Leone
            'SG' => 'SGP', //Singapore
            'SK' => 'SVK', //Slovakia
            'SI' => 'SVN', //Slovenia
            'SB' => 'SLB', //Solomon Islands
            'SO' => 'SOM', //Somalia
            'ZA' => 'ZAF', //South Africa
            'GS' => 'SGS', //South Georgia/Sandwich Islands
            'SS' => 'SSD', //South Sudan
            'ES' => 'ESP', //Spain
            'LK' => 'LKA', //Sri Lanka
            'SD' => 'SDN', //Sudan
            'SR' => 'SUR', //Suriname
            'SJ' => 'SJM', //Svalbard and Jan Mayen
            'SZ' => 'SWZ', //Swaziland
            'SE' => 'SWE', //Sweden
            'CH' => 'CHE', //Switzerland
            'SY' => 'SYR', //Syria
            'TW' => 'TWN', //Taiwan
            'TJ' => 'TJK', //Tajikistan
            'TZ' => 'TZA', //Tanzania
            'TH' => 'THA', //Thailand
            'TL' => 'TLS', //Timor-Leste
            'TG' => 'TGO', //Togo
            'TK' => 'TKL', //Tokelau
            'TO' => 'TON', //Tonga
            'TT' => 'TTO', //Trinidad and Tobago
            'TN' => 'TUN', //Tunisia
            'TR' => 'TUR', //Turkey
            'TM' => 'TKM', //Turkmenistan
            'TC' => 'TCA', //Turks and Caicos Islands
            'TV' => 'TUV', //Tuvalu
            'UG' => 'UGA', //Uganda
            'UA' => 'UKR', //Ukraine
            'AE' => 'ARE', //United Arab Emirates
            'GB' => 'GBR', //United Kingdom
            'US' => 'USA', //United States
            'UM' => 'UMI', //United States Minor Outlying Islands
            'UY' => 'URY', //Uruguay
            'UZ' => 'UZB', //Uzbekistan
            'VU' => 'VUT', //Vanuatu
            'VE' => 'VEN', //Venezuela
            'VN' => 'VNM', //Vietnam
            'VG' => 'VGB', //Virgin Islands, British
            'VI' => 'VIR', //Virgin Island, U.S.
            'WF' => 'WLF', //Wallis and Futuna
            'EH' => 'ESH', //Western Sahara
            'YE' => 'YEM', //Yemen
            'ZM' => 'ZMB', //Zambia
            'ZW' => 'ZWE', //Zimbabwe

        );

        return $countries[ $countryCode ] ?? $countryCode;
    }

}
