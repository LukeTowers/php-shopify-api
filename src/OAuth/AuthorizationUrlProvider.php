<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\OAuth;

use LukeTowers\ShopifyPHP\Credentials\ApiKey;
use LukeTowers\ShopifyPHP\Credentials\ShopDomain;

final class AuthorizationUrlProvider implements AuthorizationUrlProviderInterface
{
    private ApiKey $apiKey;
    private Scopes $scopes;
    private string $redirectUrl;

    public function __construct(ApiKey $apiKey, Scopes $scopes, string $redirectUrl)
    {
        $this->apiKey = $apiKey;
        $this->scopes = $scopes;
        $this->redirectUrl = $redirectUrl;
    }

    public function getAuthorizationUrl(
        ShopDomain $shopDomain,
        string $nonce = '',
        bool $onlineAccessMode = false
    ): string {
        $args = [
            'client_id'    => (string) $this->apiKey,
            'scope'        => (string) $this->scopes,
            'redirect_uri' => $this->redirectUrl,
            'state'        => $nonce,
        ];

        if ($onlineAccessMode) {
            $args['grant_options[]'] = 'per-user';
        }

        return "https://{$shopDomain}/admin/oauth/authorize?" . http_build_query($args);
    }
}





