<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\OAuth;

use LukeTowers\ShopifyPHP\Credentials\ShopDomain;

final class AuthorizationRequest
{
    private ShopDomain $shopDomain;
    private string $code;

    public function __construct(ShopDomain $shopDomain, string $code)
    {
        $this->shopDomain = $shopDomain;
        $this->code = $code;
    }

    public function getShopDomain(): ShopDomain
    {
        return $this->shopDomain;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
