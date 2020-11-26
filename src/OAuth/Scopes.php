<?php

declare(strict_types=1);

namespace LukeTowers\ShopifyPHP\OAuth;

final class Scopes implements \Countable, \IteratorAggregate
{
    private array $scopes;

    private function __construct(array $scopes)
    {
        if (\count($scopes) === 0) {
            throw new ScopeException('No scopes provided');
        }
        $map = \array_combine($scopes, $scopes);
        if (!\is_array($map)) {
            throw new ScopeException('Invalid scopes');
        }
        if (\count($map) !== \count($scopes)) {
            throw new ScopeException('Duplicate scopes');
        }
        \ksort($map);
        $this->scopes = $map;
    }

    public static function create($scopes): self
    {
        if (\is_string($scopes)) {
            return Scopes::fromString($scopes);
        }
        if (\is_array($scopes)) {
            return Scopes::fromArray($scopes);
        }
        throw new ScopeException('Invalid scopes value type: ' . (\is_object($scopes) ? \get_class($scopes) : \gettype($scopes)));
    }

    public static function ensure($scopes): self
    {
        if ($scopes instanceof Scopes) {
            return $scopes;
        }
        return self::create($scopes);
    }

    public static function fromArray(array $scopes): self
    {
        return new self($scopes);
    }

    public static function fromString(string $scopes): self
    {
        return new self(\explode(',', $scopes));
    }

    public function with(iterable $scopes): self
    {
        $clone = clone $this;
        foreach ($scopes as $scope) {
            $clone->scopes[$scope] = $scope;
        }
        \ksort($clone->scopes);
        return $clone;
    }

    public function without(iterable $scopes): self
    {
        $clone = clone $this;
        foreach ($scopes as $scope) {
            unset($clone->scopes[$scope]);
        }
        if (\count($clone) === 0) {
            throw new ScopeException('No scopes provided');
        }
        return $clone;
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->toArray());
    }

    public function count(): int
    {
        return \count($this->scopes);
    }

    public function toArray(): array
    {
        return \array_keys($this->scopes);
    }

    public function __toString(): string
    {
        return \implode(',', $this->scopes);
    }

    public function has(string $scope): bool
    {
        return isset($this->scopes[$scope]);
    }

    public function hasAll(iterable $scopes): bool
    {
        foreach ($scopes as $scope) {
            if (!$this->has($scope)) {
                return false;
            }
        }
        return true;
    }

    public function hasAny(iterable $scopes): bool
    {
        foreach ($scopes as $scope) {
            if ($this->has($scope)) {
                return true;
            }
        }
        return false;
    }

    public function equals($other): bool
    {
        return $this->scopes === self::ensure($other)->scopes;
    }
}
