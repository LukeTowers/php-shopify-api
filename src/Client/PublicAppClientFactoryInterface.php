<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\Client;

use LukeTowers\ShopifyPHP\Credentials\AccessToken;
use LukeTowers\ShopifyPHP\Credentials\ShopDomain;

interface PublicAppClientFactoryInterface
{
    public function createPublicAppClient(ShopDomain $shopDomain, AccessToken $accessToken): ShopifyClientInterface;
}
