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

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Helpers for working with Content-Security-Policy models.
 *
 * @internal
 */
class ModelService
{
    private const SOURCE_PARSING_PRIORITIES = [
        HashProxy::class => 50,
        HashValue::class => 50,
    ];

    /**
     * @param ?FrontendInterface $cache to be used for storing compiled CSP aspects (disabled in install tool)
     */
    public function __construct(private readonly ?FrontendInterface $cache = null) {}

    public function buildMutationSuggestionFromArray(array $array): MutationSuggestion
    {
        return new MutationSuggestion(
            $this->buildMutationCollectionFromArray($array['collection'] ?? []),
            (string)($array['identifier'] ?? ''),
            isset($array['priority']) ? (int)$array['priority'] : null,
            $array['label'] ?? null
        );
    }

    public function buildMutationCollectionFromArray(array $array): MutationCollection
    {
        $mutations = array_map(
            [$this, 'buildMutationFromArray'],
            $array['mutations'] ?? []
        );
        return new MutationCollection(...$mutations);
    }

    public function buildMutationFromArray(array $array): Mutation
    {
        return new Mutation(
            MutationMode::from($array['mode'] ?? ''),
            Directive::from($array['directive'] ?? ''),
            ...$this->buildSourcesFromItems(...($array['sources'] ?? []))
        );
    }

    public function buildSourcesFromItems(string ...$items): array
    {
        $sources = [];
        foreach ($items as $item) {
            $source = $this->buildSourceFromString($item);
            if ($source === null) {
                throw new \InvalidArgumentException(
                    sprintf('Could not convert source item "%s"', $item),
                    1677261214
                );
            }
            $sources[] = $source;
        }
        return $sources;
    }

    public function buildSourceFromString(string $string): ?SourceInterface
    {
        if (str_starts_with($string, "'nonce-") && $string[-1] === "'") {
            // use a proxy instead of a real Nonce instance
            return SourceKeyword::nonceProxy;
        }
        try {
            if ($string[0] === "'" && $string[-1] === "'") {
                return SourceKeyword::from(substr($string, 1, -1));
            }
            if ($string[-1] === ':') {
                return SourceScheme::from(substr($string, 0, -1));
            }
            return new UriValue($string);
        } catch (\InvalidArgumentException|\ValueError) {
            // no handling here
        }
        /** @var SourceValueInterface $sourceInterface */
        foreach ($this->resolvePrioritizedSourceInterfaces() as $sourceInterface) {
            if ($sourceInterface::knows($string)) {
                return $sourceInterface::parse($string);
            }
        }
        return new RawValue($string);
    }

    // @todo use SourceCollection instead?
    public function serializeSources(SourceInterface ...$sources): array
    {
        $serialized = [];
        foreach ($sources as $source) {
            if ($source instanceof SourceKeyword && $source->vetoes()) {
                $serialized = [];
            }
            $serialized[] = $this->serializeSource($source);
        }
        return $serialized;
    }

    public function compileSources(ConsumableNonce $nonce, SourceCollection $collection): array
    {
        $compiled = [];
        foreach ($collection->sources as $source) {
            if ($source instanceof SourceKeyword && $source->vetoes()) {
                $compiled = [];
            }
            if ($source instanceof SourceValueInterface) {
                $compiled[] = $source->compile($this->cache);
            } else {
                $compiled[] = $this->serializeSource($source, $nonce);
            }
        }
        return $compiled;
    }

    /**
     * @param ConsumableNonce|null $nonce used to substitute `SourceKeyword::nonceProxy` items during compilation
     */
    public function serializeSource(SourceInterface $source, ConsumableNonce $nonce = null): string
    {
        if ($source === SourceKeyword::nonceProxy && $nonce !== null) {
            return "'nonce-" . $nonce->value . "'";
        }
        if ($source instanceof SourceKeyword) {
            return "'" . $source->value . "'";
        }
        if ($source instanceof SourceScheme) {
            return $source->value . ':';
        }
        if ($source instanceof SourceValueInterface) {
            return $source->serialize();
        }
        if ($source instanceof \Stringable) {
            return (string)$source;
        }
        return '';
    }

    /**
     * Resolves reverse sorted `SourceInterface` classes (higher priorities first).
     * @return list<class-string<SourceValueInterface>>
     */
    private function resolvePrioritizedSourceInterfaces(): array
    {
        $interfaces = self::SOURCE_PARSING_PRIORITIES;
        arsort($interfaces);
        return array_keys($interfaces);
    }
}
