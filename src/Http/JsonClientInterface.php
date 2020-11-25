<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\Http;

interface JsonClientInterface
{
    /**
     * @param string $baseUrl
     * @param string $method
     * @param string $endpoint
     * @param array $headers
     * @param array|object|null $body
     * @param array<string, mixed> $query
     * @return JsonResponse
     * @throws JsonClientException
     */
    public function call(
        string $baseUrl,
        string $method = 'GET',
        string $endpoint = '',
        array $headers = [],
        $body = null,
        array $query = []
    ): JsonResponse;
}
