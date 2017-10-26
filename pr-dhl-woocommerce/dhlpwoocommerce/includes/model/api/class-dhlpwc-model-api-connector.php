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
    protected $refresh_api = 'authenticate/refresh-token';
    protected $client;

    protected $access_token;
    protected $access_token_expiration;
    protected $refresh_token;
    protected $refresh_token_expiration;

    protected $available_methods = [
        self::POST,
        self::GET
    ];

    public $is_error = false;
    public $error_id = null;
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
        $request = $this->request(self::POST, $endpoint, $params);
        if (!$request) {
            return false;
        }
        return json_decode($request['body'], true);
    }

    public function get($endpoint, $params = null)
    {
        $request = $this->request(self::GET, $endpoint, $params);
        if (!$request) {
            return false;
        }
        return json_decode($request['body'], true);
    }

    protected function request($method, $endpoint, $params = null, $retry = false)
    {
        // Assume there's always an error, until this method manages to return correctly and set the boolean to true.
        $this->is_error = true;
        $this->error_id = null;
        $this->error_message = null;

        $this->url = trailingslashit($this->url);

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

        if ($request['response']['code'] == 401 && $endpoint != $this->auth_api && $retry === false) {
            // Try again after an auth
            $this->authenticate();
            $request = $this->request($method, $endpoint, $params, true);
        }

        // TODO error handling

        if ($request['response']['code'] >= 200 && $request['response']['code'] < 300) {
            // TODO check if JSON
            $this->is_error = false;
            return $request;
        }

        if (isset($request['body'])) {
            $formatted_error = json_decode($request['body'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($formatted_error['key']) && isset($formatted_error['message'])) {
                    $this->error_id = $formatted_error['key'];
                    $this->error_message = $formatted_error['message'];
                }
            }
        }

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
                    'Authorization' => 'Bearer '.$this->access_token,
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

        $this->access_token = $response['accessToken'];
        $this->access_token_expiration = $response['accessTokenExpiration'];
        $this->refresh_token = $response['refreshToken'];
        $this->refresh_token_expiration = $response['refreshTokenExpiration'];
    }

}

endif;
