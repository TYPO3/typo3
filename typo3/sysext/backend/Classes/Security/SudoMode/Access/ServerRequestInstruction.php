<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Backend\Security\SudoMode\Access;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\Uri;

/**
 * Reduced representation of `ServerRequest` information, which is used
 * to replay the intercepted request later, once access was granted.
 *
 * @internal
 */
class ServerRequestInstruction implements \JsonSerializable
{
    /**
     * Attribute names that shall be taken from the original request
     */
    protected const KEEP_ATTRIBUTE_NAMES = [
        'applicationType',
    ];

    protected string $requestTarget;
    protected string $method;
    protected UriInterface $uri;
    protected StreamInterface $body;
    protected ?array $parsedBody;
    protected array $queryParams;
    protected array $attributes;

    public static function createForServerRequest(ServerRequestInterface $request): self
    {
        $target = new self();
        $target->requestTarget = $request->getRequestTarget();
        $target->method = $request->getMethod();
        $target->uri = self::clone($request->getUri());
        $target->body = self::clone($request->getBody());
        $target->parsedBody = self::clone($request->getParsedBody());
        $target->queryParams = $request->getQueryParams();
        $target->attributes = array_filter(
            $request->getAttributes(),
            static fn(string $name) => in_array($name, self::KEEP_ATTRIBUTE_NAMES, true),
            ARRAY_FILTER_USE_KEY
        );
        return $target;
    }

    public static function buildFromArray(array $data): self
    {
        $target = new self();
        $target->requestTarget = $data['requestTarget'];
        $target->method = $data['method'];
        $target->uri = new Uri($data['uri']);
        $target->body = new Stream('php://temp', $data['body']['mode']);
        $target->body->write($data['body']['contents']);
        $target->parsedBody = $data['parsedBody'];
        $target->queryParams = $data['queryParams'];
        $target->attributes = $data['attributes'] ?? [];
        return $target;
    }

    protected static function clone($value)
    {
        if (is_object($value)) {
            return clone $value;
        }
        return $value;
    }

    protected function __construct()
    {
        // avoid creating class instances directly from external
    }

    protected function __clone()
    {
        // avoid cloning class instances directly from external
    }

    public function jsonSerialize(): array
    {
        return [
            'class' => self::class,
            'requestTarget' => $this->requestTarget,
            'method' => $this->method,
            'uri' => (string)$this->uri,
            'body' => [
                'mode' => $this->body->getMetadata('mode'),
                'contents' => (string)$this->body,
            ],
            'parsedBody' => $this->parsedBody,
            'queryParams' => $this->queryParams,
            'attributes' => $this->attributes,
        ];
    }

    /**
     * Applies instructions to given ServerRequest ("replaying the request").
     */
    public function applyTo(ServerRequestInterface $request): ServerRequestInterface
    {
        $request = $request
            ->withRequestTarget($this->requestTarget)
            ->withMethod($this->method)
            ->withUri($this->uri)
            ->withBody($this->body)
            ->withParsedBody($this->parsedBody)
            ->withQueryParams($this->queryParams);
        foreach ($this->attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }
        return $request;
    }

    public function getRequestTarget(): string
    {
        return $this->requestTarget;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function getParsedBody(): ?array
    {
        return $this->parsedBody;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
