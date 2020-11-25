<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\Http;

final class JsonResponse
{
    private int $status;
    /** @var string[][]  */
    private array $headers;
    private array $body;

    /**
     * @param int $status
     * @param string[][] $headers
     * @param array $body
     */
    public function __construct(int $status, array $headers, array $body)
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return string[][]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): string
    {
        foreach ($this->headers as $headerName => $headerValues) {
            if (\strtolower($name) === \strtolower($headerName)) {
                return $headerValues[0] ?? '';
            }
        }
        return '';
    }

    public function getBody(): array
    {
        return $this->body;
    }
}
