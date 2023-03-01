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

use TYPO3\CMS\Core\Security\Nonce;

/**
 * A collection of sources (sic!).
 * @internal This implementation still might be adjusted
 */
final class SourceCollection
{
    /**
     * @var list<SourceKeyword|SourceScheme|Nonce|UriValue|RawValue>
     */
    public readonly array $sources;

    public function __construct(SourceKeyword|SourceScheme|Nonce|UriValue|RawValue ...$sources)
    {
        $this->sources = $sources;
    }

    public function isEmpty(): bool
    {
        return $this->sources === [];
    }

    public function merge(self $other): self
    {
        return $this->with(...$other->sources);
    }

    public function with(SourceKeyword|SourceScheme|Nonce|UriValue|RawValue ...$subjects): self
    {
        $subjects = array_filter(
            $subjects,
            fn ($subject) => !in_array($subject, $this->sources, true)
        );
        if ($subjects === []) {
            return $this;
        }
        return new self(...array_merge($this->sources, $subjects));
    }

    public function without(SourceKeyword|SourceScheme|Nonce|UriValue|RawValue ...$subjects): self
    {
        $sources = array_filter(
            $this->sources,
            fn ($source) => !in_array($source, $subjects, true)
        );
        if (count($this->sources) === count($sources)) {
            return $this;
        }
        return new self(...$sources);
    }

    /**
     * @param class-string ...$subjectTypes
     */
    public function withoutTypes(string ...$subjectTypes): self
    {
        $sources = array_filter(
            $this->sources,
            fn ($source) => !$this->isSourceOfTypes($source, ...$subjectTypes)
        );
        if (count($this->sources) === count($sources)) {
            return $this;
        }
        return new self(...$sources);
    }

    /**
     * Determines whether at least one source matches.
     */
    public function contains(SourceKeyword|SourceScheme|Nonce|UriValue|RawValue ...$subjects): bool
    {
        foreach ($this->sources as $source) {
            if (in_array($source, $subjects, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determines whether at least one type matches.
     * @param class-string ...$subjectTypes
     */
    public function containsTypes(string ...$subjectTypes): bool
    {
        foreach ($this->sources as $source) {
            if ($this->isSourceOfTypes($source, ...$subjectTypes)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param class-string ...$types
     */
    private function isSourceOfTypes(SourceKeyword|SourceScheme|Nonce|UriValue|RawValue $source, string ...$types): bool
    {
        foreach ($types as $type) {
            if (is_a($source, $type)) {
                return true;
            }
        }
        return false;
    }
}
