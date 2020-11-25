<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\OAuth;

use LukeTowers\ShopifyPHP\Credentials\ShopDomain;

final class AuthorizationRequest
{
    private ShopDomain $shopDomain;
    private string $code;
    private string $hmac;
    private string $state;

    public function __construct(ShopDomain $shopDomain, string $code, string $hmac, string $state)
    {
        $this->shopDomain = $shopDomain;
        $this->code = $code;
        $this->hmac = $hmac;
        $this->state = $state;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            ShopDomain::fromString($data['shop'] ?? ''),
            $data['code'] ?? '',
            $data['hmac'] ?? '',
            $data['state'] ?? ''
        );
    }

    public function getShopDomain(): ShopDomain
    {
        return $this->shopDomain;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getHmac(): string
    {
        return $this->hmac;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function toArray(): array
    {
        return [
            'shop' => (string) $this->shopDomain,
            'code' => $this->code,
            'hmac' => $this->hmac,
            'state' => $this->state,
        ];
    }
}
