<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\Credentials;

abstract class Credential implements \JsonSerializable
{
    protected const NOT_STRING_ERROR = 'Exected string';
    protected const EMPTY_TOKEN_ERROR = 'Value cannot be empty';
    private string $value;

    private function __construct(string $value)
    {
        if ($value === '') {
            throw new CredentialsException(static::EMPTY_TOKEN_ERROR);
        }
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new static($value);
    }

    public static function create($value): self
    {
        if (!\is_string($value)) {
            throw new CredentialsException(static::NOT_STRING_ERROR);
        }
        return static::fromString($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}
