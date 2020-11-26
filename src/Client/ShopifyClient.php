<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\Client;

use LukeTowers\ShopifyPHP\Credentials\AccessToken;
use LukeTowers\ShopifyPHP\Credentials\ApiCredentials;
use LukeTowers\ShopifyPHP\Credentials\ShopDomain;
use LukeTowers\ShopifyPHP\Http\JsonClientException;
use LukeTowers\ShopifyPHP\Http\JsonClientInterface;

final class ShopifyClient implements ShopifyClientInterface
{
    private JsonClientInterface $client;
    private ShopDomain $shopDomain;
    private array $headers;

    private function __construct(JsonClientInterface $client, ShopDomain $shopDomain, array $headers)
    {
        $this->client = $client;
        $this->shopDomain = $shopDomain;
        $this->headers = $headers;
    }

    public static function forPublicApp(JsonClientInterface $client, ShopDomain $shopDomain, AccessToken $accessToken): self
    {
        return new self($client, $shopDomain, ['X-Shopify-Access-Token' => (string) $accessToken]);
    }

    public static function forPrivateApp(JsonClientInterface $client, ShopDomain $shopDomain, ApiCredentials $credentials): self
    {
        $authHeader = 'Basic ' . \base64_encode($credentials->getApiKey() . ':' . $credentials->getSecret());
        return new self($client, $shopDomain, ['Authorization' => $authHeader]);
    }

    public function call(string $method, string $endpoint, $body = null, array $query = []): ShopifyResponse
    {
        try {
            $response = $this->client->call(
                'https://' . (string) $this->shopDomain,
                $method,
                $endpoint,
                $this->headers,
                $body,
                $query
            );
        } catch (JsonClientException $e) {
            throw new ShopifyClientException($e->getMessage(), (int) $e->getCode(), $e);
        }

        $matches = [];
        $apiCallLimitHeader = $response->getHeader('X-Shopify-Shop-Api-Call-Limit');
        if (!$apiCallLimitHeader || !\preg_match('#^(\\d+)/(\\d+)$#', $apiCallLimitHeader, $matches)) {
            return ShopifyResponse::unlimited($response->getStatus(), $response->getBody());
        }
        return ShopifyResponse::limited($response->getStatus(), $response->getBody(), (int) $matches[0], (int) $matches[1]);
    }
}
