<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\OAuth;

use LukeTowers\ShopifyPHP\Credentials\ShopDomain;

interface AuthorizationUrlProviderInterface
{
    public function getAuthorizationUrl(
        ShopDomain $shopDomain,
        string $nonce = '',
        bool $onlineAccessMode = false
    ): string;
}
