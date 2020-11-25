<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\OAuth;

use LukeTowers\ShopifyPHP\Credentials\ApiCredentials;
use LukeTowers\ShopifyPHP\Credentials\ShopDomain;
use LukeTowers\ShopifyPHP\Http\JsonClientException;
use LukeTowers\ShopifyPHP\Http\JsonClientInterface;

final class Authorizator implements AuthorizatorInterface
{
    private JsonClientInterface $client;
    private ApiCredentials $credentials;

    public function __construct(JsonClientInterface $client, ApiCredentials $credentials)
    {
        $this->client = $client;
        $this->credentials = $credentials;
    }

    public function authorizeApplication(AuthorizationRequest $request, string $nonce = '', ShopDomain $shopDomain = null): AuthorizationResponse
    {
        if ($request->getState() !== $nonce) {
            throw new AuthorizationException(\sprintf(
                "The provided nonce (%s) did not match the nonce provided by Shopify (%s)",
                $nonce,
                $request->getState()
            ));
        }

        if ($shopDomain !== null && !$shopDomain->equals($request->getShopDomain())) {
            throw new AuthorizationException(\sprintf(
                "The shop provided by Shopify (%s) does not match the shop provided to this API (%s)",
                (string) $request->getShopDomain(),
                (string) $shopDomain
            ));
        }

        // Check HMAC signature. See https://help.shopify.com/api/getting-started/authentication/oauth#verification
        $hmacSource = [];
        foreach ($request->toArray() as $key => $value) {
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
        if ($hmacString !== $request->getHmac()) {
            throw new AuthorizationException(\sprintf(
                "The HMAC provided by Shopify (%s) doesn't match the HMAC verification (%s).",
                $request->getHmac(),
                $hmacString
            ));
        }

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
}
