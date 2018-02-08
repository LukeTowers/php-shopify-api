<?php namespace LukeTowers\ShopifyPHP;

use Exception;
use GuzzleHttp\Client;

class Shopify
{
    /**
     * @var string The shop domain being interacted with
     */
    private $shop_domain;

    /**
     * @var string The API Key to use with these requests
     */
    private $api_key;

    /**
     * @var string The Secret Key associated with the provided API Key
     */
    private $secret;

    /**
     * @var string The Authentication Token to connect to the provided shop
     */
    private $token;

    /**
     * @var GuzzleHttp\Client The Client object used for requests
     */
    private $client;

    /**
     * @var array The headers from the last request
     */
    private $last_response_headers;

    /**
     * Initialize the Client
     *
     * @param string $shopDomain The Shopify Shop domain to connect to: example.myshopify.com
     * @param mixed $credentials String: The access token for the provided Shop; Array: ['api_key' => '', 'secret' => ''] The Shopify API credentials for your application
     * @return null
     */
    public function __construct(string $shopDomain, $credentials) {
        $this->shop_domain = $shopDomain;

        // Populate the credentials
        if (is_string($credentials)) {
            $this->setToken($credentials);
        } elseif (!empty($credentials['api_key']) && !empty($credentials['secret'])) {
            $this->api_key = $credentials['api_key'];
            $this->secret = $credentials['secret'];
        } else {
            throw new Exception("Unexpected value provided for the credentials");
        }

        // Initialize the client
        $this->initializeClient();
    }

    /**
     * Initialize the GuzzleHttp/Client instance
     *
     * @return GuzzleHttp/Client $client
     */
    protected function initializeClient()
    {
        if ($this->client) {
            return $this->client;
        }

        $options = [
            'base_uri'    => "https://{$this->shop_domain}/",
            'http_errors' => true,
        ];
        if (!empty($this->token)) {
            $options['headers']['X-Shopify-Access-Token'] = $this->token;
        }

        return $this->client = new Client($options);
    }

    /**
     * Set the token to be used for future requests
     *
     * @param string $token The token to use for future requests
     * @return null
     */
    public function setToken(string $token)
    {
        $this->token = $token;
        // Reset the client
        unset($this->client);
        $this->client = null;
        $this->initializeClient();
    }

    /**
     * Get the URL required to request authorization
     *
     * @param mixed $scopes The scopes to request access to
     * @param string $redirectUrl The URL to redirect to on successfull authorization
     * @param string $nonce The security token to pass to Shopify to validate the authorization request callback received from Shopify
     * @param bool $onlineAccessMode Request an Online Access Mode (user-level) token instead of a Offline Access Mode (shop-level). Default: false
     * @return string $url The Authorization URL
     */
    public function getAuthorizeUrl($scopes, string $redirectUrl, string $nonce, $onlineAccessMode = false)
    {
        if (is_string($scopes)) {
            $scopes = [$scopes];
        }

        $args = [
            'client_id'    => $this->api_key,
            'scope'        => implode(',', $scopes),
            'redirect_uri' => $redirectUrl,
            'state'        => $nonce,
        ];

        if ($onlineAccessMode) {
            $args['grant_options[]'] = 'per-user';
        }

        return "https://{$this->shop_domain}/admin/oauth/authorize?" . http_build_query($args);
    }

    /**
     * Authorize the application and return the data provided by Shopify
     *
     * @param string $nonce The nonce that was provided to Shopify in the initial authorization request
     * @param array $requestData The data that has been provided by Shopify in the callback. Expects 'code', 'hmac', 'state', 'shop', 'timestamp', but API subject to change
     * @return object $response Shopify's response to the access token request. See https://help.shopify.com/api/getting-started/authentication/oauth#step-3-confirm-installation
     */
    public function authorizeApplication(string $nonce, $requestData)
    {
        $requiredKeys = ['code', 'hmac', 'state', 'shop'];
        foreach ($requiredKeys as $required) {
            if (!in_array($required, array_keys($requestData))) {
                throw new Exception("The provided request data is missing one of the following keys: " . implode(', ', $requiredKeys));
            }
        }

        if ($requestData['state'] !== $nonce) {
            throw new Exception("The provided nonce ($nonce) did not match the nonce provided by Shopify ({$requestData['state']})");
        }

        if (!filter_var($requestData['shop'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new Exception("The shop provided by Shopify ({$requestData['shop']}) is an invalid hostname.");
        }

        if ($requestData['shop'] !== $this->shop_domain) {
            throw new Exception("The shop provided by Shopify ({$requestData['shop']}) does not match the shop provided to this API ({$this->shop_domain})");
        }

        // Check HMAC signature. See https://help.shopify.com/api/getting-started/authentication/oauth#verification
        $hmacSource = [];
        foreach ($requestData as $key => $value) {
            // Skip the hmac key
            if ($key === 'hmac') { continue; }

            // Replace the characters as specified by Shopify in the keys and values
            $valuePatterns = [
                '&' => '%26',
                '%' => '%25',
            ];
            $keyPatterns = array_merge($valuePatterns, ['=' => '%3D']);
            $key = str_replace(array_keys($keyPatterns), array_values($keyPatterns), $key);
            $value = str_replace(array_keys($valuePatterns), array_values($valuePatterns), $value);

            $hmacSource[] = $key . '=' . $value;
        }

        // Sort the key value pairs lexographically and then generate the HMAC signature of the provided data
        sort($hmacSource);
        $hmacBase = implode('&', $hmacSource);
        $hmacString = hash_hmac('sha256', $hmacBase, $this->secret);

        // Verify that the signatures match
        if ($hmacString !== $requestData['hmac']) {
            throw new Exception("The HMAC provided by Shopify ({$requestData['hmac']}) doesn't match the HMAC verification ($hmacString).");
        }

        // Make the access token request to Shopify
        try {
            $response = $this->client->request('POST', 'admin/oauth/access_token', [
                'body' => json_encode([
                    'client_id'     => $this->api_key,
                    'client_secret' => $this->secret,
                    'code'          => $requestData['code'],
                ]),
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        } catch (Exception $e) {
            // Pass the erroring response direct to browser
            die($e->getResponse()->getBody());
        }

        // Decode the response from Shopify
        $data = json_decode($response->getBody());

        // Set the access token
        $this->setToken($data->access_token);

        // Return the result of the authorization attempt
        return $data;
    }

    /**
     * Make an API call to Shopify
     *
     * @param string $method The method to use to call Shopify
     * @param string $endpoint The endpoint to access on Shopify
     * @param array $params The parameters to provide in the API request
     * @return mixed $response
     */
    public function call(string $method, string $endpoint, $params = [])
    {
        $method = strtoupper($method);
        $options = [];

        // Use the API credentials to authenticate as a private application
        if (empty($this->token)) {
            $options['headers']['Authorization'] = 'Basic ' . base64_encode($this->api_key . ':' . $this->secret);
        }

        // Prepare the request based on the method used
        switch ($method) {
            case 'GET':
            case 'DELETE':
                $options['query'] = $params;
                break;

            case 'PUT':
            case 'POST':
                $options['body'] = json_encode($params);
                $options['headers']['Content-Type'] = 'application/json';
                break;
        }

        // Make the request
        $response = $this->client->request($method, $endpoint, $options);

        // Store the response headers for later usage
        $this->last_response_headers = $response->getHeaders();

        // Return the response body
        return json_decode($response->getBody());
    }

    /**
     * Get the number of calls that have been made since the bucket was empty
     *
     * @return int $calls
     */
    public function getCallsMade()
    {
        return $this->getCallLimitHeaderValue()[0];
    }

    /**
     * Get the number of calls that the bucket can support
     *
     * @return int $calls
     */
    public function getCallLimit()
    {
        return $this->getCallLimitHeaderValue()[1];
    }

    /**
     * Get the number of calls remaining before the bucket is full
     *
     * @return int $calls
     */
    public function getCallsRemaining()
    {
        return $this->getCallLimit() - $this->getCallsMade();
    }

    /**
     * Get the call amount information from the last API request
     *
     * @return array $calls [$callsMade, $callsRemaining]
     */
    protected function getCallLimitHeaderValue()
    {
        if (!$this->last_response_headers) {
            throw new Exception("Call limits can't be polled before a request has been made.");
        }

        return explode('/', $this->last_response_headers['X-Shopify-Shop-Api-Call-Limit']);
    }
}
