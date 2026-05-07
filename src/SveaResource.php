<?php

declare(strict_types=1);

namespace Svea;

use ArrayAccess;
use Svea\Transport\SveaResponse;

/**
 * Base class for all Svea API response objects.
 *
 * Holds the raw API data array and the last HTTP response, and provides
 * transparent read-only access via magic properties and array notation.
 *
 * Features:
 * - Magic property read access: `$order->OrderId`
 * - Read-only {@see ArrayAccess}: `$order['OrderId']`
 * - Raw HTTP response via {@see getLastResponse()} for debugging
 * - Named `make()` constructor for clean instantiation in tests and services
 *
 * Concrete subclasses extend this and add named typed getters
 * (e.g. `id()`, `status()`, `successful()`).
 *
 * Response objects are intentionally **read-only** after construction.
 * Calling `offsetSet()` or `offsetUnset()` will throw {@see \BadMethodCallException}.
 */
/** @implements ArrayAccess<string, mixed> */
abstract class SveaResource implements ArrayAccess
{
    private ?SveaResponse $lastResponse = null;

    /**
     * @param  array<string, mixed>  $data  Raw API response data.
     */
    public function __construct(protected array $data = []) {}

    /**
     * Named constructor — creates an instance pre-populated with the given data.
     *
     * @param  array<string, mixed>  $data  Raw API response data.
     */
    public static function make(array $data = []): static
    {
        return new static($data); // @phpstan-ignore new.static
    }

    /**
     * Read a raw field by name.
     *
     * @return mixed The field value, or null if not present.
     */
    public function __get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Check whether a raw field is present and non-null.
     */
    public function __isset(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    /**
     * Not supported — response objects are read-only.
     *
     * @throws \BadMethodCallException Always.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \BadMethodCallException('Svea response objects are read-only.');
    }

    /**
     * Not supported — response objects are read-only.
     *
     * @throws \BadMethodCallException Always.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new \BadMethodCallException('Svea response objects are read-only.');
    }

    /**
     * Attach the raw HTTP response to this object.
     *
     * Returns the same instance for fluent chaining:
     * ```php
     * return CheckoutResponse::make($response->json)->withLastResponse($response);
     * ```
     *
     * Called by service classes immediately after constructing the response object.
     */
    public function withLastResponse(SveaResponse $response): static
    {
        $this->lastResponse = $response;

        return $this;
    }

    /**
     * Return the raw HTTP response attached to this object, if any.
     *
     * Useful for debugging status codes, headers, and raw response bodies
     * without throwing exceptions.
     */
    public function getLastResponse(): ?SveaResponse
    {
        return $this->lastResponse;
    }

    /**
     * Return the underlying raw data array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
