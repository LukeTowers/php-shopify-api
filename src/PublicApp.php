<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP;

use LukeTowers\ShopifyPHP\Client\ShopifyClientInterface;
use LukeTowers\ShopifyPHP\Credentials\AccessToken;
use LukeTowers\ShopifyPHP\Credentials\ApiKey;
use LukeTowers\ShopifyPHP\Credentials\ShopDomain;
use LukeTowers\ShopifyPHP\OAuth\AuthorizationException;
use LukeTowers\ShopifyPHP\OAuth\AuthorizationRequest;
use LukeTowers\ShopifyPHP\OAuth\AuthorizationResponse;
use LukeTowers\ShopifyPHP\OAuth\Scopes;

class PublicApp
{
    private ShopifyService $shopify;
    private Scopes $scopes;
    private string $redirectUrl;

    public function __construct(ShopifyService $shopify, Scopes $scopes, string $redirectUrl)
    {
        $this->shopify = $shopify;
        $this->scopes = $scopes;
        $this->redirectUrl = $redirectUrl;
    }

    public function getApiKey(): ApiKey
    {
        return $this->shopify->getApiKey();
    }

    public function validateShopRequest(array $requestData)
    {
        return $this->shopify->validateShopRequest($requestData);
    }

    public function getAuthorizationUrl(
        ShopDomain $shopDomain,
        string $nonce = '',
        bool $onlineAccessMode = false
    ): string {
        return $this->shopify->getAuthorizationUrl(
            $shopDomain,
            $this->scopes,
            $this->redirectUrl,
            $nonce,
            $onlineAccessMode
        );
    }

    public function validateAuthorizationRequest(array $requestData, string $nonce = '', ?ShopDomain $shopDomain = null): AuthorizationRequest
    {
        return $this->shopify->validateAuthorizationRequest($requestData, $nonce, $shopDomain);
    }

    public function authorizeApplication(AuthorizationRequest $request): AuthorizationResponse
    {
        $response =  $this->shopify->authorizeApplication($request);
        if (!$response->getScopes()->hasAll($this->scopes)) {
            throw new AuthorizationException(\sprintf(
                'The user did not grant all required scopes (required: %s, granted: %s)',
                (string) $this->scopes,
                (string) $response->getScopes()
            ));
        }
        return $response;
    }

    public function createPublicAppClient(ShopDomain $shopDomain, AccessToken $accessToken): ShopifyClientInterface
    {
        return $this->shopify->createPublicAppClient($shopDomain, $accessToken);
    }
}
