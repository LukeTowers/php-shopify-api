<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\Credentials;

final class ApiCredentials
{
    private ApiKey $apiKey;
    private ApiSecretKey $secret;

    public function __construct(ApiKey $apiKey, ApiSecretKey $secret)
    {
        $this->apiKey = $apiKey;
        $this->secret = $secret;
    }

    public static function create(string $apiKey, string $secret): self
    {
        return new self(ApiKey::fromString($apiKey), ApiSecretKey::fromString($secret));
    }

    public function getApiKey(): ApiKey
    {
        return $this->apiKey;
    }

    public function getSecret(): ApiSecretKey
    {
        return $this->secret;
    }
}
