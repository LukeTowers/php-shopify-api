<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP;

use LukeTowers\ShopifyPHP\Client\PrivateAppClientFactoryInterface;
use LukeTowers\ShopifyPHP\Client\PublicAppClientFactoryInterface;
use LukeTowers\ShopifyPHP\Client\ShopifyClient;
use LukeTowers\ShopifyPHP\Client\ShopifyClientInterface;
use LukeTowers\ShopifyPHP\Credentials\AccessToken;
use LukeTowers\ShopifyPHP\Credentials\ApiCredentials;
use LukeTowers\ShopifyPHP\Credentials\ShopDomain;
use LukeTowers\ShopifyPHP\Http\JsonClient;
use LukeTowers\ShopifyPHP\Http\JsonClientInterface;
use LukeTowers\ShopifyPHP\OAuth\Authorizator;
use LukeTowers\ShopifyPHP\OAuth\AuthorizatorFactoryInterface;
use LukeTowers\ShopifyPHP\OAuth\AuthorizatorInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class ShopifyService implements
    PrivateAppClientFactoryInterface,
    PublicAppClientFactoryInterface,
    AuthorizatorFactoryInterface
{
    private JsonClientInterface $client;

    public function __construct(JsonClientInterface $client)
    {
        $this->client = $client;
    }

    public static function create(RequestFactoryInterface $requestFactory, ClientInterface $client): self
    {
        return new self(new JsonClient($requestFactory, $client));
    }

    public function createPrivateAppClient(ShopDomain $shopDomain, ApiCredentials $credentials): ShopifyClientInterface
    {
        $authHeader = 'Basic ' . \base64_encode($credentials->getApiKey() . ':' . $credentials->getSecret());
        return new ShopifyClient($this->client, $shopDomain, ['Authorization' => $authHeader]);
    }

    public function createPublicAppClient(ShopDomain $shopDomain, AccessToken $accessToken): ShopifyClientInterface
    {
        return new ShopifyClient($this->client, $shopDomain, ['X-Shopify-Access-Token' => (string) $accessToken]);
    }

    public function createAuthorizator(ApiCredentials $credentials): AuthorizatorInterface
    {
        return new Authorizator($this->client, $credentials);
    }
}
