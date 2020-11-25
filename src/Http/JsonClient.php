<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class JsonClient implements JsonClientInterface
{
    private RequestFactoryInterface $requestFactory;
    private ClientInterface $client;

    public function __construct(RequestFactoryInterface $requestFactory, ClientInterface $client)
    {
        $this->requestFactory = $requestFactory;
        $this->client = $client;
    }

    public function call(
        string $baseUrl,
        string $method = 'GET',
        string $endpoint = '',
        array $headers = [],
        $body = null,
        array $query = []
    ): JsonResponse {

        $uri = $baseUrl
            . ($endpoint !== '' && $endpoint[0] !== '/' ? '/' : '')
            . $endpoint
            . ($query ? '?' . \http_build_query($query) : '');
        $request = $this->requestFactory->createRequest($method, $uri);

        foreach ($headers as $headerName => $headerValue) {
            $request = $request->withHeader($headerName, $headerValue);
        }

        if ($body !== null) {
            $request = $request->withHeader('Content-Type', 'application/json');
            $request->getBody()->write(\json_encode($body));
        } else {
            $request = $request->withoutHeader('Content-Type');
        }

        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new JsonClientException("Failed request: $method $uri: " . $e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        $responseJson = [];
        if (self::hasJsonBody($response)) {
            $responseJson = \json_decode((string) $response->getBody(), true);
            if (!\is_array($responseJson)) {
                throw new JsonClientException("Could not decode json response of $method $uri", $status);
            }
        }

        return new JsonResponse($status, $response->getHeaders(), $responseJson);
    }

    private static function hasJsonBody(ResponseInterface $response): bool
    {
        if ($response->getBody()->getSize() === 0) {
            return false;
        }
        if (!$response->hasHeader('Content-Type')) {
            return true;
        }
        return \strpos($response->getHeader('Content-Type')[0], 'application/json') !== false;
    }
}
