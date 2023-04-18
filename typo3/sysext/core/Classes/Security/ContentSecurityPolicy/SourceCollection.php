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

use TYPO3\CMS\Core\Domain\EqualityInterface;

/**
 * A collection of sources (sic!).
 * @internal This implementation still might be adjusted
 */
final class SourceCollection
{
    /**
     * @var list<SourceInterface>
     */
    public readonly array $sources;

    public function __construct(SourceInterface ...$sources)
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

    public function exclude(self $other): self
    {
        return $this->without(...$other->sources);
    }

    public function with(SourceInterface ...$subjects): self
    {
        $uniqueSubjects = [];
        foreach ($subjects as $subject) {
            if (!(in_array($subject, $uniqueSubjects, true)
                    || ($subject instanceof EqualityInterface && $this->hasEqualSource($subject, ...$uniqueSubjects)))
                && !(in_array($subject, $this->sources, true)
                    || ($subject instanceof EqualityInterface && $this->hasEqualSource($subject, ...$this->sources)))
            ) {
                $uniqueSubjects[] = $subject;
            }
        }
        if ($uniqueSubjects === []) {
            return $this;
        }
        return new self(...array_merge($this->sources, $uniqueSubjects));
    }

    public function without(SourceInterface ...$subjects): self
    {
        $sources = array_filter(
            $this->sources,
            fn ($source) => !(in_array($source, $subjects, true)
                || ($source instanceof EqualityInterface && $this->hasEqualSource($source, ...$subjects)))
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
     * Determines whether all sources are contained (in terms of instances and values, but without inference).
     */
    public function contains(SourceInterface ...$subjects): bool
    {
        if ($subjects === []) {
            return false;
        }
        foreach ($subjects as $subject) {
            if ($subject instanceof EqualityInterface) {
                if (!$this->hasEqualSource($subject, ...$this->sources)) {
                    return false;
                }
            } elseif (!in_array($subject, $this->sources, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Determines whether all sources are covered (in terms of CSP inference, considering wildcards and similar).
     */
    public function covers(SourceInterface ...$subjects): bool
    {
        if ($subjects === []) {
            return false;
        }
        foreach ($subjects as $subject) {
            if ($subject instanceof CoveringInterface) {
                if (!$this->hasCoveredSource($subject)) {
                    return false;
                }
            } elseif (!in_array($subject, $this->sources, true)) {
                return false;
            }
        }
        return true;
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

    private function hasEqualSource(EqualityInterface $subject, SourceInterface ...$sources): bool
    {
        foreach ($sources as $source) {
            if ($source instanceof EqualityInterface && $source->equals($subject)) {
                return true;
            }
        }
        return false;
    }

    private function hasCoveredSource(CoveringInterface $subject): bool
    {
        foreach ($this->sources as $source) {
            if ($source instanceof CoveringInterface && $source->covers($subject)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param class-string ...$types
     */
    private function isSourceOfTypes(SourceInterface $source, string ...$types): bool
    {
        foreach ($types as $type) {
            if (is_a($source, $type)) {
                return true;
            }
        }
        return false;
    }
}
