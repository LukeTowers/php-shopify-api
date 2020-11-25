<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\OAuth;

final class OnlineAuthorizationInfo
{
    private int $expiresIn;
    private Scopes $associatedUserScopes;
    private AuthorizationUser $associatedUser;

    public function __construct(int $expiresIn, Scopes $associatedUserScopes, AuthorizationUser $associatedUser)
    {
        $this->expiresIn = $expiresIn;
        $this->associatedUserScopes = $associatedUserScopes;
        $this->associatedUser = $associatedUser;
    }

    public static function tryFromArray(array $data): ?self
    {
        if (isset($data['associated_user_scope'])) {
            return self::fromArray($data);
        }
        return null;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['expires_in'],
            Scopes::create($data['associated_user_scope'] ?? null),
            AuthorizationUser::fromArray($data['associated_user'] ?? null)
        );
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function getAssociatedUserScopes(): Scopes
    {
        return $this->associatedUserScopes;
    }

    public function getAssociatedUser(): AuthorizationUser
    {
        return $this->associatedUser;
    }
}
