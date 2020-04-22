<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class PR_DHL_API_Paket extends PR_DHL_API {

	const DHL_PAKET_DISPLAY_DAYS = 5;
	const DHL_PAKET_REMOVE_DAYS = 2;

	private $de_national_holidays = array('2017-07-02','2017-07-09','2017-07-16','2017-07-23','2017-07-30','2017-08-06','2017-08-13','2017-08-20','2017-08-27','2017-09-03','2017-09-10','2017-09-17','2017-09-24','2017-10-01','2017-10-03','2017-10-08','2017-10-15','2017-10-22','2017-10-29','2017-11-05','2017-11-12','2017-11-19','2017-11-26','2017-12-03','2017-12-10','2017-12-17','2017-12-24','2017-12-25','2017-12-26','2017-12-31','2018-01-01','2018-01-07','2018-01-14','2018-01-21','2018-01-28','2018-02-04','2018-02-11','2018-02-18','2018-02-25','2018-03-04','2018-03-11','2018-03-18','2018-03-25','2018-03-30','2018-04-01','2018-04-02','2018-04-08','2018-04-15','2018-04-22','2018-04-29','2018-05-01','2018-05-06','2018-05-10','2018-05-13','2018-05-20','2018-05-21','2018-05-27','2018-06-03','2018-06-10','2018-06-17','2018-06-24','2018-07-01','2018-07-08','2018-07-15','2018-07-22','2018-07-29','2018-08-05','2018-08-12','2018-08-19','2018-08-26','2018-09-02','2018-09-09','2018-09-16','2018-09-23','2018-09-30','2018-10-03','2018-10-07','2018-10-14','2018-10-21','2018-10-28','2018-11-04','2018-11-11','2018-11-18','2018-11-25','2018-12-02','2018-12-09','2018-12-16','2018-12-23','2018-12-25','2018-12-26','2018-12-30','2019-01-01','2019-01-06','2019-01-13','2019-01-20','2019-01-27','2019-02-03','2019-02-10','2019-02-17','2019-02-24','2019-03-03','2019-03-10','2019-03-17','2019-03-24','2019-03-31','2019-04-07','2019-04-14','2019-04-19','2019-04-21','2019-04-22','2019-04-28','2019-05-01','2019-05-05','2019-05-12','2019-05-19','2019-05-26','2019-05-30','2019-06-02','2019-06-09','2019-06-10','2019-06-16','2019-06-23','2019-06-30','2019-07-07','2019-07-14','2019-07-21','2019-07-28','2019-08-04','2019-08-11','2019-08-18','2019-08-25','2019-09-01','2019-09-08','2019-09-15','2019-09-22','2019-09-29','2019-10-03','2019-10-06','2019-10-13','2019-10-20','2019-10-27','2019-11-03','2019-11-10','2019-11-17','2019-11-24','2019-12-01','2019-12-08','2019-12-15','2019-12-22','2019-12-25','2019-12-26','2019-12-29','2020-01-01','2020-01-05','2020-01-12','2020-01-19','2020-01-26','2020-02-02','2020-02-09','2020-02-16','2020-02-23','2020-03-01','2020-03-08','2020-03-15','2020-03-22','2020-03-29','2020-04-05','2020-04-10','2020-04-12','2020-04-13','2020-04-19','2020-04-26','2020-05-01','2020-05-03','2020-05-10','2020-05-17','2020-05-21','2020-05-24','2020-05-31','2020-06-01','2020-06-07','2020-06-14','2020-06-21','2020-06-28','2020-07-05','2020-07-12','2020-07-19','2020-07-26','2020-08-02','2020-08-09','2020-08-16','2020-08-23','2020-08-30','2020-09-06','2020-09-13','2020-09-20','2020-09-27','2020-10-03','2020-10-04','2020-10-11','2020-10-18','2020-10-25','2020-11-01','2020-11-08','2020-11-15','2020-11-22','2020-11-29','2020-12-06','2020-12-13','2020-12-20','2020-12-25','2020-12-26','2020-12-27','2017-10-31','2017-12-23');

	public function __construct( $country_code ) {
		$this->country_code = $country_code;
		try {
			$this->dhl_label = new PR_DHL_API_SOAP_Label( );
			$this->dhl_finder = new PR_DHL_API_SOAP_Finder( );
		} catch (Exception $e) {
			throw $e;	
		}
	}

	public function is_dhl_paket( ) {
		return true;
	}
	
	public function get_dhl_products_international() {
		$country_code = $this->country_code;
		
		$germany_int =  array( 
								'V55PAK' => __('DHL Paket Connect', 'pr-shipping-dhl'),
								'V54EPAK' => __('DHL Europaket (B2B)', 'pr-shipping-dhl'),
								'V53WPAK' => __('DHL Paket International', 'pr-shipping-dhl'),
								);

		$dhl_prod_int = array();

		switch ($country_code) {
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
								'V01PAK' => __('DHL Paket', 'pr-shipping-dhl'),
								'V01PRIO' => __('DHL Paket PRIO', 'pr-shipping-dhl'),
								);

		$dhl_prod_dom = array();

		switch ($country_code) {
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
		$exclude_sun = array( 'Sun' => __('sun', 'pr-shipping-dhl') );
		$exclude_working_days += $exclude_sun;
		$day_counter = 0;

		// Get existing timezone to reset afterwards
		$current_timzone = date_default_timezone_get();
		// Always set and get DE timezone and check against it. 
		date_default_timezone_set('Europe/Berlin');

		// Get existing time locale
		// $current_locale = setlocale(LC_TIME, 0);
		// Set time locale based on WP locale setting (Settings->General)
		// $wp_locale = get_locale();
		// setlocale(LC_TIME, $wp_locale);
		// setlocale(LC_TIME, 'de_DE', 'deu_deu', 'de_DE.utf8', 'German', 'deu/ger', 'de_DE@euro', 'de', 'ge');
		
		$tz_obj = new DateTimeZone( 'Europe/Berlin' );
		$today = new DateTime("now", $tz_obj);	// Should the order date be passed as a variable?
		$today_de_timestamp = $today->getTimestamp();

		$week_day = $today->format('D');
		$week_date = $today->format('Y-m-d');
		$week_time = $today->format('H:i');

		// Compare week day with key since key includes capital letter in beginning and will work for English AND German!
		// Check if today is a working day...
		if ( ( ! array_key_exists($week_day, $exclude_working_days) ) && ( ! in_array($week_date, $this->de_national_holidays) ) ) {
			// ... and check if after cutoff time if today is a transfer day
			if( $today_de_timestamp >= strtotime( $cutoff_time ) ) {
				// If the cut off time has been passed, then add a day
				$today->add( new DateInterval('P1D') ); // Add 1 day
				$week_day = $today->format('D');
				$week_date = $today->format('Y-m-d');

				$day_counter++;

			}
		}

		// Make sure the next transfer days are working days
		while ( array_key_exists($week_day, $exclude_working_days) || in_array($week_date, $this->de_national_holidays) ) {
			$today->add( new DateInterval('P1D') ); // Add 1 day
			$week_day = $today->format('D');
			$week_date = $today->format('Y-m-d');

			$day_counter++;
		}

		$args['postcode'] = $postcode;
		$args['account_num'] = $account_num;
		$args['start_date'] = $week_date;
		$dhl_parcel_services = new PR_DHL_API_REST_Parcel();
		$preferred_services = $dhl_parcel_services->get_dhl_parcel_services($args);
		
		$preferred_day_time = array();
		$preferred_day_time['preferred_day'] = $this->get_dhl_preferred_day( $preferred_services );

		// Reset time locael
		// setlocale(LC_TIME, $current_locale);
		// Reset timezone to not affect any other plugins
		date_default_timezone_set($current_timzone);

		return $preferred_day_time;
	}

	protected function get_dhl_preferred_day( $preferred_services ) {
		$day_of_week_arr = array(
		            '1' => __('Mon', 'pr-shipping-dhl'), 
		            '2' => __('Tue', 'pr-shipping-dhl'), 
		            '3' => __('Wed', 'pr-shipping-dhl'),
		            '4' => __('Thu', 'pr-shipping-dhl'),
		            '5' => __('Fri', 'pr-shipping-dhl'),
		            '6' => __('Sat', 'pr-shipping-dhl'),
		            '7' => __('Sun', 'pr-shipping-dhl')
		        );
		
		$preferred_days = array();
		if( isset( $preferred_services->preferredDay->available ) && $preferred_services->preferredDay->available && isset( $preferred_services->preferredDay->validDays ) ) {

			foreach ($preferred_services->preferredDay->validDays as $days_key => $days_value) {
				$temp_day_time = strtotime( $days_value->start );

				$day_of_week = date('N', $temp_day_time );
				$week_date = date('Y-m-d', $temp_day_time );

				$preferred_days[ $week_date ] = $day_of_week_arr[ $day_of_week ];
			}
			
			// Add none option
			array_unshift( $preferred_days, __('none', 'pr-shipping-dhl') );
		}


		return $preferred_days;
	}

	public function get_dhl_duties() {
		$duties = parent::get_dhl_duties();

		$duties_paket = array(
					'DXV' => __('Delivery Duty Paid (excl. VAT )', 'pr-shipping-dhl'),
					'DDX' => __('Delivery Duty Paid (excl. Duties, taxes and VAT)', 'pr-shipping-dhl')
					);
		$duties += $duties_paket;

		return $duties;
	}

	public function get_dhl_visual_age() {
		$visual_age = array(
					'0' => _x('none', 'age context', 'pr-shipping-dhl'),
					'A16' => __('Minimum age of 16', 'pr-shipping-dhl'),
					'A18' => __('Minimum age of 18', 'pr-shipping-dhl')
					);
		return $visual_age;
	}
}
