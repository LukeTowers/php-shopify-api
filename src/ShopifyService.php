<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP;

use LukeTowers\ShopifyPHP\Client\ShopifyClient;
use LukeTowers\ShopifyPHP\Client\ShopifyClientInterface;
use LukeTowers\ShopifyPHP\Credentials\AccessToken;
use LukeTowers\ShopifyPHP\Credentials\ApiCredentials;
use LukeTowers\ShopifyPHP\Credentials\ApiKey;
use LukeTowers\ShopifyPHP\Credentials\ShopDomain;
use LukeTowers\ShopifyPHP\Http\JsonClient;
use LukeTowers\ShopifyPHP\Http\JsonClientException;
use LukeTowers\ShopifyPHP\Http\JsonClientInterface;
use LukeTowers\ShopifyPHP\OAuth\AuthorizationException;
use LukeTowers\ShopifyPHP\OAuth\AuthorizationRequest;
use LukeTowers\ShopifyPHP\OAuth\AuthorizationResponse;
use LukeTowers\ShopifyPHP\OAuth\Scopes;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

final class ShopifyService
{
    private JsonClientInterface $client;
    private ApiCredentials $credentials;

    public function __construct(JsonClientInterface $client, ApiCredentials $credentials)
    {
        $this->client = $client;
        $this->credentials = $credentials;
    }

    public static function create(RequestFactoryInterface $requestFactory, ClientInterface $client, ApiCredentials $credentials): self
    {
        return new self(new JsonClient($requestFactory, $client), $credentials);
    }

    public function getApiKey(): ApiKey
    {
        return $this->credentials->getApiKey();
    }

    public function validateShopRequest(array $requestData): ShopDomain
    {
        try {
            return ShopDomain::create($requestData['shop'] ?? null);
        } catch (\Throwable $e) {
            throw new AuthorizationException("The shop provided by Shopify is invalid: " . $e->getMessage());
        }
    }

    public function getAuthorizationUrl(
        ShopDomain $shopDomain,
        Scopes $scopes,
        string $redirectUrl,
        string $nonce = '',
        bool $onlineAccessMode = false
    ): string {
        $args = [
            'client_id'    => (string) $this->credentials->getApiKey(),
            'scope'        => (string) $scopes,
            'redirect_uri' => $redirectUrl,
            'state'        => $nonce,
        ];

        if ($onlineAccessMode) {
            $args['grant_options[]'] = 'per-user';
        }

        return "https://{$shopDomain}/admin/oauth/authorize?" . http_build_query($args);
    }

    public function validateAuthorizationRequest(array $requestData, string $nonce = '', ?ShopDomain $shopDomain = null): AuthorizationRequest
    {
        $requestShopDomain = $this->validateShopRequest($requestData);
        $requiredKeys = ['code', 'hmac', 'state'];
        foreach ($requiredKeys as $required) {
            if (!in_array($required, array_keys($requestData))) {
                throw new AuthorizationException(
                    "The provided request data is missing one of the following keys: " . implode(', ', $requiredKeys)
                );
            }
        }

        if ($requestData['state'] !== $nonce) {
            throw new AuthorizationException("The provided nonce ($nonce) did not match the nonce provided by Shopify ({$requestData['state']})");
        }

        if ($shopDomain !== null && !$shopDomain->equals($requestShopDomain)) {
            throw new AuthorizationException(\sprintf(
                "The shop provided by Shopify (%s) does not match the shop provided to this API (%s)",
                (string) $requestShopDomain,
                (string) $shopDomain
            ));
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
        $hmacString = hash_hmac('sha256', $hmacBase, (string) $this->credentials->getSecret());

        // Verify that the signatures match
        if ($hmacString !== $requestData['hmac']) {
            throw new AuthorizationException(\sprintf(
                "The HMAC provided by Shopify (%s) doesn't match the HMAC verification (%s).",
                $requestData['hmac'],
                $hmacString
            ));
        }

        return new AuthorizationRequest($requestShopDomain, $requestData['code']);
    }

    public function authorizeApplication(AuthorizationRequest $request): AuthorizationResponse
    {
        try {
            $response = $this->client->call(
                (string) $request->getShopDomain(),
                'POST',
                '/admin/oauth/access_token',
                [],
                [
                    'client_id'     => (string) $this->credentials->getApiKey(),
                    'client_secret' => (string) $this->credentials->getSecret(),
                    'code'          => $request->getCode(),
                ],
                );
        } catch (JsonClientException $e) {
            throw new AuthorizationException('Authorization request failed: ' . $e->getMessage(), 0, $e);
        }

        $status = $response->getStatus();
        if ($status !== 200) {
            throw new AuthorizationException('Unexpected authorization response status ' . $status, $status);
        }
        return AuthorizationResponse::fromArray($response->getBody());
    }

    public function createPublicAppClient(ShopDomain $shopDomain, AccessToken $accessToken): ShopifyClientInterface
    {
        return new ShopifyClient($this->client, $shopDomain, ['X-Shopify-Access-Token' => (string) $accessToken]);
    }

    public function createPrivateAppClient(ShopDomain $shopDomain): ShopifyClientInterface
    {
        $authHeader = 'Basic ' . \base64_encode($this->credentials->getApiKey() . ':' . $this->credentials->getSecret());
        return new ShopifyClient($this->client, $shopDomain, ['Authorization' => $authHeader]);
    }

    public function createPublicApp(Scopes $scopes, string $redirectUrl): PublicApp
    {
        return new PublicApp($this, $scopes, $redirectUrl);
    }
}
