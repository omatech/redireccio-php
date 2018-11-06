<?php

namespace Omatech\RedireccIO;

use GuzzleHttp\Client as Http;
use GuzzleHttp\Exception\ClientException;

class Client
{
    /**
     * @var GuzzleHttp\Client
     */
    private $http;
		

    /**
     * @var RedireccioConfiguration
     */
    private $config;

    /**
     * RedirectIOClient constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->http = new Http([
            'base_uri' => $config['server'],
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Token '. $config['token']
            ]
        ]);
    }

    public function intercept($url, $method)
    {
        if(array_key_exists('enabled', $this->config) && $this->config['enabled']) {
            $this->validateUrl($url, $method);
        }
    }

    private function validateUrl($url, $method)
    {
        try {
            $response = $this->http->post('api/check', [
                'form_params' => [
                    'url' => $url,
                    'method' => $method
                ]
            ]);

            $response = json_decode($response->getBody()->getContents(), true);

            if($response['status'] === 200) {
                if(array_key_exists('error', $response)) {
                    $this->writeLog($response['error']['status'], $response['error']['message']);
                }
                elseif(array_key_exists('redirect', $response)) {
                    $this->redirect($response['redirect']['to'],
                                    $response['redirect']['status'],
                                    $response['redirect']['status_string']
                    );
                }
            }
        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            $this->writeLog($response['status'], $response['message']);
        }
    }

    private function redirect($to, $code, $status)
    {
        header("HTTP/1.1 $status", true, $code);
        header("Location: $to");
        die();
    }

    private function writeLog($status, $message)
    {
        error_log("RedireccIO: $status - $message");
    }
}
