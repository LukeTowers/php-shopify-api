<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\Credentials;

final class ShopDomain
{
    private string $shopName;

    private function __construct(string $shopName)
    {
        $this->shopName = $shopName;
    }

    public static function create($value): self
    {
        if (!\is_string($value)) {
            throw new ShopDomainException(
                'Expected shop domain, got' . (\is_object($value) ? \get_class($value) : \gettype($value))
            );
        }
        return self::fromString($value);
    }

    public function fromShopName(string $shopName): self
    {
        $matches = [];
        if (!\preg_match('/^\\S+$/', $shopName, $matches)) {
            throw new ShopDomainException("Invalid shop name: \"$shopName\".");
        }
        return new self($matches[0]);
    }

    public static function fromString(string $shopDomain): self
    {
        $matches = [];
        if (!\preg_match('/^(\\S+)\.myshopify\.com$/', $shopDomain, $matches)) {
            throw new ShopDomainException("Invalid shop domain: \"$shopDomain\".");
        }
        return new self($matches[1]);
    }

    public function equals(ShopDomain $other): bool
    {
        return $this->shopName === $other->shopName;
    }

    public function __toString(): string
    {
        return $this->shopName . '.myshopify.com';
    }

    public function getShopName(): string
    {
        return $this->shopName;
    }

    public function getShopUrl(): string
    {
        return 'https://' . (string) $this;
    }
}
