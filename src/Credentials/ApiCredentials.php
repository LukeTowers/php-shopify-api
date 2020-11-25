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

    public function getApiKey(): ApiKey
    {
        return $this->apiKey;
    }

    public function getSecret(): ApiSecretKey
    {
        return $this->secret;
    }
}
