<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Response_HTML')) :

class DHLPWC_Model_Response_HTML
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

    public function __toString()
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

        return json_encode($response, JSON_PRETTY_PRINT);
    }

}

endif;
