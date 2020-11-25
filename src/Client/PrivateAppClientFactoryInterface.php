<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\Client;

use LukeTowers\ShopifyPHP\Credentials\ApiCredentials;
use LukeTowers\ShopifyPHP\Credentials\ShopDomain;

interface PrivateAppClientFactoryInterface
{
    public function createPrivateAppClient(ShopDomain $shopDomain, ApiCredentials $credentials): ShopifyClientInterface;
}
