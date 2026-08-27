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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

use Psr\Http\Message\UriInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Page\ResourceHashCollection;
use TYPO3\CMS\Core\SystemResource\Type\StaticResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\UriResource;

/**
 * Per-request registry collecting CSP hash values for inline and static assets.
 * Hash values are preferred over nonce values as they allow response caching.
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final class DirectiveHashCollection implements \JsonSerializable
{
    /**
     * @var array{
     *     inline: array<string, list<HashValue>>,
     *     resource: array<string, list<HashValue>>,
     *     uri: array<string, list<HashValue>>,
     *     generic: array<string, list<HashValue>>
     * }
     */
    private array $hashValues = [
        'inline' => [],
        'resource' => [],
        'uri' => [],
        'generic' => [],
    ];

    public function __construct(private readonly ResourceHashCollection $resourceHashCollection) {}

    /**
     * Computes a SHA-256 hash of the given inline content and stores it for the directive.
     */
    public function addInlineHash(Directive $directive, string $content): void
    {
        $this->addHashValue($directive, HashValue::hash($content), 'inline');
    }

    /**
     * Lazily computes a hash from a local file identified by an EXT: path or absolute path.
     * No-op if the file cannot be read.
     */
    public function addResourceHash(Directive $directive, string|UriInterface|StaticResourceInterface $resource): void
    {
        if (is_string($resource)) {
            $resource = $this->resourceHashCollection->resolveResourceValue($resource);
            if ($resource === null) {
                return;
            }
        }
        $hashValue = $this->resourceHashCollection->fetchResourceHash($resource);
        if ($hashValue === null) {
            return;
        }
        if ($resource instanceof UriInterface || $resource instanceof UriResource) {
            $this->addHashValue($directive, $hashValue, 'uri');

        } else {
            $this->addHashValue($directive, $hashValue, 'resource');
        }
    }

    /**
     * Stores an already-computed HashValue (e.g. parsed from an `integrity` attribute).
     */
    public function addGenericHashValue(Directive $directive, HashValue|string $hashValue): void
    {
        if (is_string($hashValue)) {
            $hashValue = $this->convertHashValue($hashValue);
        }
        if ($hashValue !== null) {
            $this->addHashValue($directive, $hashValue, 'generic');
        }
    }

    /**
     * Converts all stored hashes into MutationCollection instances that can be applied to the CSP.
     * Hash values from all types are merged per directive.
     *
     * @return list<MutationCollection>
     */
    public function asMutationCollections(): array
    {
        $byDirective = [];
        foreach ($this->hashValues as $typeHashValues) {
            foreach ($typeHashValues as $directiveName => $hashValues) {
                $byDirective[$directiveName] = array_merge($byDirective[$directiveName] ?? [], $hashValues);
            }
        }
        $collections = [];
        foreach ($byDirective as $directiveName => $hashValues) {
            $directive = Directive::from($directiveName);
            // filter out duplicates
            $stringHashValues = array_unique(array_map(strval(...), $hashValues));
            $hashValues = array_map(HashValue::fromString(...), $stringHashValues);
            // convert to CSP mutation
            $mutations = array_map(
                static fn(HashValue $hash): Mutation => new Mutation(MutationMode::Extend, $directive, $hash),
                $hashValues
            );
            $collections[] = new MutationCollection(...$mutations);
        }
        return $collections;
    }

    public function isEmpty(): bool
    {
        foreach ($this->hashValues as $typeHashValues) {
            if ($typeHashValues !== []) {
                return false;
            }
        }
        return true;
    }

    public function jsonSerialize(): array
    {
        $serialized = [];
        foreach ($this->hashValues as $type => $typeHashValues) {
            foreach ($typeHashValues as $directiveName => $hashValues) {
                $serialized[$type][$directiveName] = array_map(
                    static fn(HashValue $hashValue): string => $hashValue->export(),
                    $hashValues
                );
            }
        }
        return $serialized;
    }

    /**
     * Restores hash values from a previously serialized (cached) state.
     */
    public function updateFromJson(array $data): void
    {
        foreach ($data as $type => $typeHashValues) {
            foreach ($typeHashValues as $directiveName => $hashItems) {
                $directive = Directive::from($directiveName);
                foreach ($hashItems as $item) {
                    $this->addHashValue($directive, HashValue::fromString($item), $type);
                }
            }
        }
    }

    private function addHashValue(Directive $directive, HashValue $hashValue, string $type): void
    {
        $this->hashValues[$type][$directive->value] ??= [];
        $this->hashValues[$type][$directive->value][] = $hashValue;
    }

    private function convertHashValue(string $hashValue): ?HashValue
    {
        try {
            return HashValue::fromString($hashValue);
        } catch (\LogicException) {
            // hash format not recognized, skip
            return null;
        }
    }
}
