<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\OAuth;

use LukeTowers\ShopifyPHP\Credentials\ApiCredentials;

interface AuthorizatorFactoryInterface
{
    public function createAuthorizator(ApiCredentials $credentials): AuthorizatorInterface;
}
