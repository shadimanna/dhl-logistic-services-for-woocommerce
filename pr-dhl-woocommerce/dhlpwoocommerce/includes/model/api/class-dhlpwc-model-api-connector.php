<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Connector')) :

class DHLPWC_Model_API_Connector extends DHLPWC_Model_Core_Singleton_Abstract
{

    const POST = 'post';
    const GET = 'get';

    protected $user_id = null;
    protected $key = null;

    protected $url = 'https://api-gw.dhlparcel.nl/'; // TODO generic environment setting

    protected $auth_api = 'authenticate/api-key';
    protected $access_token;

    /* Experimental */
    protected $accounts = array();

    protected $available_methods = [
        self::POST,
        self::GET
    ];

    public $is_error = false;
    public $error_id = null;
    public $error_code = null;
    public $error_message = null;

    public function __construct()
    {
        // Debug
        $service = DHLPWC_Model_Service_Access_Control::instance();
        $debug_url = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEBUG);
        if ($debug_url) {
            $this->url = $debug_url;
        }
    }

    public function getAvailableMethods()
    {
        return $this->available_methods;
    }

    public function post($endpoint, $params = null)
    {
        $params = $params ? json_encode($params) : null;
        $response = $this->request(self::POST, $endpoint, $params);
        return $this->parse_response($response, $endpoint, $params);
    }

    public function get($endpoint, $params = null, $cache_time = 0)
    {
        if ($cache_time) {
            $cache_id = $endpoint.'_'.crc32(json_encode($params));
            $cache = get_transient('dhlpwc_connector_cache_' . $cache_id);
            if (!empty($cache)) {
                return $cache;
            }
        }

        $response = $this->request(self::GET, $endpoint, $params);
        $parsed_response = $this->parse_response($response, $endpoint, $params);
        if ($parsed_response && $cache_time) {
            $cache_id = $endpoint . '_' . crc32(json_encode($params));
            set_transient('dhlpwc_connector_cache_' . $cache_id, $parsed_response, $cache_time);
        }
        return $parsed_response;
    }

    protected function parse_response($response, $endpoint = null, $params = null)
    {
        if (!$response || !is_array($response) || !array_key_exists('body', $response)) {
            // Something unexpected happened. Send debug mail if enabled
            $service = DHLPWC_Model_Service_Access_Control::instance();
            if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEBUG_MAIL)) {
                $service = DHLPWC_Model_Service_Debug::instance();
                $service->mail($this->error_id, $this->error_code, $this->error_message, $endpoint, $params);
            }
            return false;
        }
        return json_decode($response['body'], true);
    }

    protected function request($method, $endpoint, $params = null)
    {
        // Assume there's always an error, until this method manages to return correctly and set the boolean to true.
        $this->is_error = true;
        $this->error_id = null;
        $this->error_code = null;
        $this->error_message = null;

        $this->url = trailingslashit($this->url);

        if (!isset($this->access_token) && $endpoint != $this->auth_api) {
            $this->authenticate();
        }

        $add_bearer = ($endpoint != $this->auth_api && isset($this->access_token)) ? true : false;
        $request_params = $this->generate_params($params, $add_bearer);

        if ($method == self::POST) {
            $request = wp_remote_post($this->url.$endpoint, $request_params);
        } else if ($method == self::GET) {
            $request_params['data_format'] = 'query';
            $request = wp_remote_get($this->url.$endpoint, $request_params);
        } else {
            throw new Exception('Unknown method type');
        }

        if ($request instanceof WP_Error) {
            return false;
        }

        if ($request['response']['code'] >= 200 && $request['response']['code'] < 300) {
            if (array_key_exists('body', $request)) {
                json_decode($request['body'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->is_error = false;
                    return $request;
                }
            }
        }

        if (isset($request['body'])) {
            $formatted_error = json_decode($request['body'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($formatted_error['key']) && isset($formatted_error['message'])) {
                    $this->error_id = $formatted_error['key'];
                    $this->error_message = $formatted_error['message'];

                    // Add error details if exists
                    if (isset($formatted_error['details']) && is_array($formatted_error['details'])) {
                        $this->error_message .= ' Details: ' . json_encode($formatted_error['details']);
                    }
                }
            }
        }

        $this->error_code = $request['response']['code'];

        return false;
    }

    protected function generate_params($params, $add_bearer = true)
    {
        $request_params = [];
        $request_params = array_merge_recursive($request_params, array(
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ),
        ));

        if ($add_bearer) {
            $request_params = array_merge_recursive($request_params, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->access_token,
                ),
            ));
        }

        if ($params) {
            $request_params = array_merge_recursive($request_params, array(
                'body' => $params,
            ));
        }

        return $request_params;
    }

    protected function load_api_settings()
    {
        $service = DHLPWC_Model_Service_Settings::instance();

        $this->user_id = $service->get_api_user();
        $this->key = $service->get_api_key();
    }

    protected function authenticate()
    {
        $this->load_api_settings();

        $response = $this->post($this->auth_api, array(
            'userId' => $this->user_id,
            'key' => $this->key,
        ));

        if (!empty($response['accessToken'])) {
            $this->access_token = $response['accessToken'];
        }
    }

    public function test_authenticate($user_id, $key)
    {
        $response = $this->post($this->auth_api, array(
            'userId' => $user_id,
            'key' => $key,
        ));
        if (!isset($response['accessToken'])) {
            return false;
        }

        /* Experimental */
        if (isset($response['accounts'])) {
            $this->accounts = $response['accounts'];
        } else {
            $this->accounts = $this->parse_token($response['accessToken'], 'accounts');
        }

        return array(
            'accounts' => $this->accounts
        );
    }

    protected function parse_token($token, $key)
    {
        // Retrieve middle part
        $token_parts = explode('.', $token);
        if (count($token_parts) < 2) {
            return false;
        }

        // Base64 decode
        $json_data = base64_decode($token_parts[1]);
        if (!$json_data) {
            return false;
        }

        // Json decode
        $data = json_decode($json_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        // Find key
        if (!isset($data[$key])) {
            return false;
        }

        return $data[$key];
    }

}

endif;
