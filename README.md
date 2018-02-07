# About

A simple PHP wrapper around the [Shopify API](https://help.shopify.com/api/getting-started).

## Installation

Install via [Composer](https://getcomposer.org/) by running `composer require luketowers/php-shopify-api` in your project directory.

## Usage

In order to use this wrapper library you will need to provide credentials to access Shopify's API.

You will either need an access token for the shop you are trying to access (if using a [public application](https://help.shopify.com/api/getting-started/authentication#public-applications)) or an API Key and Secret for a [private application](https://help.shopify.com/api/getting-started/authentication#private-applications).

## Examples

#### Make an API call
```php
use LukeTowers\ShopifyPHP\Shopify;

// Initialize the client
$api = new Shopify('exampleshop.myshopify.com', 'mysupersecrettoken');

// Get all products
$result = $api->call('GET', 'admin/products.json');

// Get the products with ids of '632910392' and '921728736' with only the 'id', 'images', and 'title' fields
$result = $api->call('GET', 'admin/products.json', [
    'ids' => '632910392,921728736',
    'fields' => 'id,images,title',
]);

// Create a new "Burton Custom Freestyle 151" product
$result = $api->call('POST', 'admin/products.json', [
    'product' => [
        "title"        => "Burton Custom Freestyle 151",
        "body_html"    => "<strong>Good snowboard!</strong>",
        "vendor"       => "Burton",
        "product_type" => "Snowboard",
        "tags"         => 'Barnes & Noble, John's Fav, "Big Air"',
    ],
]);
```

#### Use Private Application API Credentials to authenticate API requests
```php
use LukeTowers\ShopifyPHP\Shopify;

$api = new Shopify($data['shop'], [
    'api_key' => '...',
    'secret'  => '...',
]);
```

#### Use an access token to authenticate API requests
```php
use LukeTowers\ShopifyPHP\Shopify;

$storedToken = ''; // Retrieve the stored token for the shop in question
$api = new Shopify('exampleshop.myshopify.com', $storedToken);
```

#### Request an access_token for a shop
```php
use LukeTowers\ShopifyPHP\Shopify;

function make_authorization_attempt($shop, $scopes)
{
    $api = new Shopify($shop, [
        'api_key' => '...',
        'secret'  => '...',
    ]);

    $nonce = bin2hex(random_bytes(10));

    // Store a record of the shop attempting to authenticate and the nonce provided
    $storedAttempts = file_get_contents('authattempts.json');
    $storedAttempts = $storedAttempts ? json_decode($storedAttempts) : [];
    $storedAttempts[] = ['shop' => $shop, 'nonce' => $nonce, 'scopes' => $scopes];
    file_put_contents('authattempts.json', json_encode($storedAttempts));

    return $api->getAuthorizeUrl($scopes, 'https://example.com/handle/shopify/callback', $nonce);
}

header('Location: ' . make_authorization_attempt('exampleshop.myshopify.com', ['read_product']));
die();
```

#### Handle Shopify's response to the authorization request
```php
use LukeTowers\ShopifyPHP\Shopify;

function check_authorization_attempt()
{
    $data = $_GET;

    $api = new Shopify($data['shop'], [
        'api_key' => '...',
        'secret'  => '...',
    ]);

    $storedAttempt = null;
    $attempts = json_decode(file_get_contents('authattempts.json'));
    foreach ($attempts as $attempt) {
        if ($attempt->shop === $data['shop']) {
            $storedAttempt = $attempt;
            break;
        }
    }

    return $api->authorizeApplication($storedAttempt->nonce, $data);
}

$response = check_authorization_attempt();
if ($response) {
    // Store the access token for later use
    $response->access_token;
}
```
