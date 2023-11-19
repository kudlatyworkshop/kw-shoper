<?php

namespace KWShoper;

/**
 * Class ShoperApi
 *
 * A class for interacting with the Shoper API.
 *
 * @package     KWShoper
 * @author      KudlatyWORKSHOP.com <hello@kudlatyworkshop.com>
 * @copyright   Copyright (C), 2023 KudlatyWORKSHOP.com
 * @version     1.0.0
 * 
 */
class ShoperApi
{
    /**
     * @var string The Shoper shop URL.
     */
    private $shopUrl;

    /**
     * @var string The client_id for authentication.
     */
    private $clientId;

    /**
     * @var string The client_secret for authentication.
     */
    private $clientSecret;

    /**
     * @var string|null The access token obtained during authentication.
     */
    private $accessToken;

    /**
     * @var int|null The expiration timestamp of the access token.
     */
    private $tokenExpiration;

    /**
     * ShoperApi constructor.
     *
     * @param string $shopUrl      The Shoper shop URL.
     * @param string $clientId     The client_id for authentication.
     * @param string $clientSecret The client_secret for authentication.
     *
     * @throws \InvalidArgumentException If client_id or client_secret is null.
     */
    public function __construct($shopUrl, $clientId, $clientSecret)
    {
        if (empty($clientId) || empty($clientSecret)) {
            throw new \InvalidArgumentException("ClientId and clientSecret cannot be null.");
        }

        $this->shopUrl = rtrim($shopUrl, '/') . '/webapi/rest/';
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->authenticate();
    }

    /**
     * Get headers for API requests.
     *
     * @param bool $includeAccessToken Whether to include the access token in headers.
     *
     * @return array The array of headers.
     */
    private function headers($includeAccessToken = true)
    {
        $headers = [
            'Content-Type: application/json',
        ];

        if ($includeAccessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        return $headers;
    }

    /**
     * Authenticate with the Shoper API.
     *
     * @return bool True if authentication is successful, false otherwise.
     * @throws \Exception If authentication fails.
     */
    private function authenticate()
    {
        if (!$this->accessToken || $this->tokenExpired()) {
            $url = $this->shopUrl . 'auth';
            $data = [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ];

            $options = [
                'http' => [
                    'header' => implode("\r\n", $this->headers(false)),
                    'method' => 'POST',
                    'ignore_errors' => true,
                ],
            ];

            $context = stream_context_create($options);

            try {
                $response = @file_get_contents($url . '?' . http_build_query($data), false, $context);

                if ($response !== false) {
                    $responseData = json_decode($response, true);

                    if (isset($responseData['access_token'])) {
                        $this->accessToken = $responseData['access_token'];
                        $this->tokenExpiration = time() + $responseData['expires_in'];
                        return true;
                    } else {
                        error_log("Authentication failed. Response: " . print_r($responseData, true));
                    }
                } else {
                    error_log("Authentication request failed. Check network or server issues.");
                }
            } catch (\Exception $e) {
                error_log("Exception during authentication: " . $e->getMessage());
                throw new \Exception("Authentication failed");
            }
            return false;
        }
        return true;
    }

    /**
     * Check if the access token has expired.
     *
     * @return bool True if the access token has expired, false otherwise.
     */
    private function tokenExpired()
    {
        return $this->tokenExpiration !== null && $this->tokenExpiration < time();
    }

    /**
     * Make a request to the Shoper API.
     *
     * @param string $endpoint The API endpoint.
     * @param string $method   The HTTP method (GET, POST, PUT, DELETE).
     * @param array  $data     The data to send in the request body for POST or PUT requests.
     *
     * @return mixed The API response.
     */
    public function call($endpoint, $method = 'GET', $data = [])
    {
        if (!$this->authenticate()) {
            return "Authentication failed";
        }

        $response = '';

        $url = $this->shopUrl . $endpoint;
        $options = [
            'http' => [
                'header' => implode("\r\n", $this->headers()),
                'method' => strtoupper($method),
            ],
        ];

        if (in_array(strtoupper($method), ['POST', 'PUT'])) {
            $options['http']['header'] .= "\r\n" . 'Content-Type: application/json';
            $options['http']['content'] = json_encode($data);
        }

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        $httpCode = isset($http_response_header) ? explode(' ', $http_response_header[0])[1] : null;

        if ($httpCode !== null && substr($httpCode, 0, 1) != '2') {
            return "Error: $http_response_header[0]\r";
        } else {
            return json_decode($response, true);
        }
    }
}
