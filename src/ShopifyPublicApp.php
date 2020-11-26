<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP;

use LukeTowers\ShopifyPHP\Client\ShopifyClientInterface;
use LukeTowers\ShopifyPHP\Credentials\AccessToken;
use LukeTowers\ShopifyPHP\Credentials\ApiKey;
use LukeTowers\ShopifyPHP\Credentials\ShopDomain;
use LukeTowers\ShopifyPHP\Credentials\ShopDomainException;
use LukeTowers\ShopifyPHP\OAuth\AuthorizationException;
use LukeTowers\ShopifyPHP\OAuth\AuthorizationRequest;
use LukeTowers\ShopifyPHP\OAuth\AuthorizationResponse;
use LukeTowers\ShopifyPHP\OAuth\ScopeException;
use LukeTowers\ShopifyPHP\OAuth\Scopes;

class ShopifyPublicApp
{
    private Shopify $shopify;
    private string $redirectUrl;
    private Scopes $requiredScopes;
    private ?Scopes $optionalScopes;

    public function __construct(Shopify $shopify, string $redirectUrl, Scopes $requiredScopes, ?Scopes $optionalScopes = null)
    {
        if ($optionalScopes !== null && $requiredScopes->hasAny($optionalScopes)) {
            throw new \InvalidArgumentException(\sprintf(
                'Required and optional scopes must be disjoint sets (required: %s, optional: %s)',
                (string) $requiredScopes,
                (string) $optionalScopes
            ));
        }
        $this->shopify = $shopify;
        $this->redirectUrl = $redirectUrl;
        $this->requiredScopes = $requiredScopes;
        $this->optionalScopes = $optionalScopes;
    }

    public function getApiKey(): ApiKey
    {
        return $this->shopify->getApiKey();
    }

    /**
     * @param array $requestData
     * @return ShopDomain
     * @throws ShopDomainException
     */
    public function validateShopRequest(array $requestData): ShopDomain
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
            $this->optionalScopes ? $this->requiredScopes->with($this->optionalScopes) : $this->requiredScopes,
            $this->redirectUrl,
            $nonce,
            $onlineAccessMode
        );
    }

    /**
     * @param array $requestData
     * @return ShopDomain
     * @throws AuthorizationException
     */
    public function validateSecuredRequest(array $requestData): ShopDomain
    {
        return $this->shopify->validateSecuredRequest($requestData);
    }

    /**
     * @param array $requestData
     * @param string $nonce
     * @param ShopDomain|null $shopDomain
     * @return AuthorizationRequest
     * @throws AuthorizationException
     */
    public function validateAuthorizationRequest(array $requestData, string $nonce = '', ?ShopDomain $shopDomain = null): AuthorizationRequest
    {
        return $this->shopify->validateAuthorizationRequest($requestData, $nonce, $shopDomain);
    }

    /**
     * @param AuthorizationRequest $request
     * @return AuthorizationResponse
     * @throws AuthorizationException
     */
    public function authorizeApplication(AuthorizationRequest $request): AuthorizationResponse
    {
        $response =  $this->shopify->authorizeApplication($request);

        try {
            $notGrantedScopes = $this->requiredScopes->without($response->getScopes());
        } catch (ScopeException $e) {
            // empty scopes set is not allowed, but here it means all scopes were granted
            return $response;
        }

        throw new AuthorizationException(\sprintf(
            'The user did not grant all required scopes (required: %s, not granted: %s)',
            (string) $this->requiredScopes,
            (string) $notGrantedScopes
        ));
    }

    public function createPublicAppClient(ShopDomain $shopDomain, AccessToken $accessToken): ShopifyClientInterface
    {
        return $this->shopify->createPublicAppClient($shopDomain, $accessToken);
    }
}
