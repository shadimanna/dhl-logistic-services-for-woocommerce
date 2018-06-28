<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Response_JSON')) :

class DHLPWC_Model_Response_JSON
{

    protected $error = false;
    protected $data = array();
    protected $messages = array();

    public function set_error($message)
    {
        $this->error = true;
        $this->messages[] = $message;
    }

    /**
     * @param array $data
     */
    public function set_data($data)
    {
        $this->data = $data;
    }

    public function to_array()
    {
        return $this->prepare_response();
    }

    public function __toString()
    {
        $response = $this->prepare_response();
        return json_encode($response, JSON_PRETTY_PRINT);
    }

    protected function prepare_response()
    {
        if ($this->error) {
            $response = array(
                'status' => 'error',
                'data' => null,
                'message' => implode(PHP_EOL, $this->messages)
            );
        } else {
            $response = array(
                'status' => 'success',
                'data' => $this->data,
                'message' => null
            );
        }

        return $response;
    }

}

endif;
