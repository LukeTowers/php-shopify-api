<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\Credentials;

final class ApiSecretKey extends Credential
{
    protected const NOT_STRING_ERROR = 'API secret key must be a string.';
    protected const EMPTY_TOKEN_ERROR = 'Empty API secret key.';
}
