<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\OAuth;

use LukeTowers\ShopifyPHP\Credentials\AccessToken;

final class AuthorizationResponse
{
    private AccessToken $accessToken;
    private Scopes $scopes;
    private ?OnlineAuthorizationInfo $onlineInfo;

    public function __construct(AccessToken $accessToken, Scopes $scopes, ?OnlineAuthorizationInfo $onlineInfo = null)
    {
        $this->accessToken = $accessToken;
        $this->scopes = $scopes;
        $this->onlineInfo = $onlineInfo;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            AccessToken::create($data['access_token'] ?? null),
            Scopes::create($data['scope'] ?? null),
            OnlineAuthorizationInfo::tryFromArray($data)
        );
    }

    public function getAccessToken(): AccessToken
    {
        return $this->accessToken;
    }

    public function getScopes(): Scopes
    {
        return $this->scopes;
    }

    public function getOnlineInfo(): ?OnlineAuthorizationInfo
    {
        return $this->onlineInfo;
    }
}
