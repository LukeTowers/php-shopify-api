<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\Client;

use LukeTowers\ShopifyPHP\ShopifyExceptionInterface;

interface ShopifyClientInterface
{
    /**
     * @param string $method
     * @param string $endpoint
     * @param null $body
     * @param array $query
     * @return ShopifyResponse
     * @throws ShopifyExceptionInterface
     */
    public function call(string $method, string $endpoint, $body = null, array $query = []): ShopifyResponse;
}
