<?php

namespace Shopify;

use Curl\Curl;

class Shopify
{
    /**
     * Request URI format
     */
    const REQUEST_URI = 'https://{apiKey}:{apiSecret}@{shop}/admin/api/{version}/{resource_uri}.json';

    /**
     * Shopify API version
     * @var string
     */
    private $version = '2019-07';

    /**
     * Api key
     * @var
     */
    public $apiKey;

    /**
     * API secret
     * @var
     */
    public $apiSecret;

    /**
     * Access Token
     * @var string
     */
    private $accessToken;

    /**
     * Shop
     * @var string
     */
    public $shop;

    /**
     * Holds the Curl object
     * @var Curl|null
     */
    private $client = null;

    /**
     * Shopify constructor.
     * @param $shop
     * @param $apiKey
     * @param $apiSecret
     */
    public function __construct($shop, $apiKey, $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->shop = $shop;
        $this->client = new Curl();
    }

    /**
     * @param $accessToken
     */
    public function setAccessToken($accessToken)
    {
        // set the access token
        $this->accessToken = $accessToken;

        // Set custom headers for curl
        $this->setHeader();
    }

    /**
     * @return mixed
     */
    public function getAccessToken()
    {
        $accessToken = '';
        if (isset($this->accessToken) && !empty($this->accessToken)) {
            $accessToken = $this->accessToken;
        } else {
            $params = $_GET;
            $url = "https://{$this->shop}/admin/oauth/access_token";
            $payload = "client_id={$this->apiKey}&client_secret={$this->apiSecret}&code={$params['code']}";
            try {
                $response = $this->client->post($url, $params);
            } catch (\Exception $e) {
            }
            $response = json_decode($response, true);
            if (isset($response['access_token']))
                $accessToken = $response['access_token'];
        }
        return $accessToken;
    }

    /**
     * Set the Shopify API version.
     *
     * @param $version string
     */
    public function setApiVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Set custom header for Shopify CURL call
     * @return void
     */
    private function setHeader()
    {
        $this->client->headers = [
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json'
        ];
    }

    /**
     * @param $permissions
     * @param $redirect_uri
     * @param bool $auto_redirect
     * @return string
     */
    public function installURL($permissions, $redirect_uri, $auto_redirect = true)
    {
        $oauthUrl = "https://{$this->shop}.myshopify.com/admin/oauth/authorize?client_id={$this->apiKey}
        &scope={$permissions}";
        if (!empty($redirect_uri)) {
            $redirect_uri = urlencode($redirect_uri);
            $oauthUrl .= "&redirect_uri={$redirect_uri}";
        }
        if ($auto_redirect) {
            header("Location: $oauthUrl");
        }
        return $oauthUrl;
    }

    /**
     * @return \Curl\CurlResponse
     */
    public function getProducts()
    {
        $products = [];
        $url = $this->formatRequestURI('products.json');
        try {
            $products = $this->client->get($url);
            $products = json_decode($products->body, true);
        } catch (\Exception $e) {

        }
        return $products;
    }

    /**
     * @param $resource_uri
     * @return string
     */
    private function formatRequestURI($resource_uri)
    {
        $resource_uri = preg_replace('/^\//', '', $resource_uri, 1);
        return strtr(self::REQUEST_URI, [
            '{apiKey}' => $this->apiKey,
            '{apiSecret}' => $this->apiSecret,
            '{shop}' => $this->shop,
            '{version}' => $this->version,
            '{resource_uri}' => $resource_uri,
        ]);
    }

    /**
     * Make http call depending upon http method.
     * @param $method
     * @param $resource
     * @param array $payload
     * @return array|mixed|string
     * @throws \Exception
     */
    public function call($method, $resource, $payload = [])
    {
        $resourceURI = '';
        if (!empty($resource)) {
            $resourceURI = $this->formatRequestURI($resource);
        }
        switch (strtoupper($method)) {
            case 'GET':
                $response = $this->client->get($resourceURI, $payload)->body;
                break;
            case 'POST':
                // Post call
                $response = $this->client->post($resourceURI, $payload)->body;
                break;
            case 'DELETE':
                // DELETE call
                $response = $this->client->request($method, $resourceURI)->headers;
                break;
            default:
                $response = $this->client->request($method, $resourceURI, $payload)->body;
        }
        return $response;
    }

    /**
     * Make GET HTTP call to Shopify API resource.
     * @param $resource
     * @param $payload
     * @return array|mixed|string
     * @throws \Exception
     */
    public function get($resource, $payload = [])
    {
        $response = $this->call('GET', $resource, $payload);
        $response = json_decode($response, true);
        if (empty($response['errors'])) {
            $response = reset($response);
        }
        return $response;
    }

    /**
     * Make POST HTTP call to Shopify API resource.
     *
     * @param $resource
     * @param $payload
     * @return mixed
     * @throws \Exception
     */
    public function post($resource, $payload)
    {
        // Make POST Http request
        $response = $this->call('POST', $resource, $payload);
        return $this->formatResponse($response);
    }

    /**
     * Make PUT HTTP call to Shopify API resource.
     * @param $resource
     * @param $payload
     * @return mixed
     * @throws \Exception
     */
    public function put($resource, $payload)
    {
        // Make PUT Http request
        $response = $this->call('PUT', $resource, $payload);
        return $this->formatResponse($response);
    }

    /**
     * Make DELETE HTTP call to Shopify API resource.
     * @param $resource
     * @return bool
     * @throws \Exception
     */
    public function delete($resource)
    {
        // Make DELETE Http request
        $status = $this->call('DELETE', $resource)['Status-Code'];
        if ($status) {
            return true;
        }
        return false;
    }

    /**
     * Format the response body.
     * @param $response
     * @return mixed
     */
    private function formatResponse($response)
    {
        $response = json_decode($response, true);
        if (empty($response['errors'])) {
            $response = reset($response);
        }
        return $response;
    }

}