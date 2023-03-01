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
use TYPO3\CMS\Core\Type\Map;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Representation of the whole Content-Security-Policy
 * see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy
 *
 * @internal This implementation still might be adjusted
 */
class Policy implements \Stringable
{
    /**
     * @var Map<Directive, SourceCollection>
     */
    protected Map $directives;

    /**
     * @param Nonce $nonce used to substitute `SourceKeyword::nonceProxy` items during compilation
     * @param SourceCollection|SourceKeyword|SourceScheme|Nonce|UriValue|RawValue ...$sources (optional) default-src sources
     */
    public function __construct(
        protected readonly Nonce $nonce,
        SourceCollection|SourceKeyword|SourceScheme|Nonce|UriValue|RawValue ...$sources
    ) {
        $this->directives = new Map();
        $collection = $this->asMergedSourceCollection(...$sources);
        if (!$collection->isEmpty()) {
            $this->directives[Directive::DefaultSrc] = $collection;
        }
    }

    public function __toString(): string
    {
        return $this->compile();
    }

    public function isEmpty(): bool
    {
        return count($this->directives) === 0;
    }

    /**
     * Applies mutations/changes to the current policy.
     */
    public function mutate(MutationCollection|Mutation ...$mutations): self
    {
        $self = $this;
        foreach ($mutations as $mutation) {
            if ($mutation instanceof MutationCollection) {
                $self = $self->mutate(...$mutation->mutations);
            } elseif ($mutation->mode === MutationMode::Set) {
                $self = $self->set($mutation->directive, ...$mutation->sources);
            } elseif ($mutation->mode === MutationMode::Extend) {
                $self = $self->extend($mutation->directive, ...$mutation->sources);
            } elseif ($mutation->mode === MutationMode::Remove) {
                $self = $self->remove($mutation->directive);
            }
        }
        return $self;
    }

    /**
     * Sets (overrides) the 'default-src' directive, which is also the fall-back for other more specific directives.
     */
    public function default(SourceCollection|SourceKeyword|SourceScheme|Nonce|UriValue|RawValue ...$sources): self
    {
        return $this->set(Directive::DefaultSrc, ...$sources);
    }

    /**
     * Extends a specific directive, either by appending sources or by inheriting from an ancestor directive.
     */
    public function extend(
        Directive $directive,
        SourceCollection|SourceKeyword|SourceScheme|Nonce|UriValue|RawValue ...$sources
    ): self {
        $collection = $this->asMergedSourceCollection(...$sources);
        if ($collection->isEmpty()) {
            return $this;
        }
        foreach ($directive->getAncestors() as $ancestorDirective) {
            if (isset($this->directives[$ancestorDirective])) {
                $ancestorCollection = $this->directives[$ancestorDirective];
                break;
            }
        }
        $targetCollection = $this->asMergedSourceCollection(...array_filter([
            $ancestorCollection ?? null,
            $this->directives[$directive] ?? null,
            $collection,
        ]));
        return $this->set($directive, $targetCollection);
    }

    /**
     * Sets (overrides) a specific directive.
     */
    public function set(
        Directive $directive,
        SourceCollection|SourceKeyword|SourceScheme|Nonce|UriValue|RawValue ...$sources
    ): self {
        $collection = $this->asMergedSourceCollection(...$sources);
        if ($collection->isEmpty()) {
            return $this;
        }
        $target = clone $this;
        $target->directives[$directive] = $collection;
        return $target;
    }

    /**
     * Removes a specific directive.
     */
    public function remove(Directive $directive): self
    {
        if (!isset($this->directives[$directive])) {
            return $this;
        }
        $target = clone $this;
        unset($target->directives[$directive]);
        return $target;
    }

    /**
     * Sets the 'report-uri' directive and appends 'report-sample' to existing & applicable directives.
     */
    public function report(UriValue $reportUri): self
    {
        $target = $this->set(Directive::ReportUri, $reportUri);
        $reportSample = SourceKeyword::reportSample;
        foreach ($target->directives as $directive => $collection) {
            if ($reportSample->isApplicable($directive)) {
                $target->directives[$directive] = $collection->with($reportSample);
            }
        }
        return $target;
    }

    /**
     * Prepares the policy for finally being serialized and issued as HTTP header.
     * This step aims to optimize several combinations, or adjusts directives when 'strict-dynamic' is used.
     */
    public function prepare(): self
    {
        $purged = false;
        $directives = clone $this->directives;
        $comparator = [$this, 'compareSources'];
        foreach ($directives as $directive => $collection) {
            foreach ($directive->getAncestors() as $ancestorDirective) {
                $ancestorCollection = $directives[$ancestorDirective] ?? null;
                if ($ancestorCollection !== null
                    && array_udiff($collection->sources, $ancestorCollection->sources, $comparator) === []
                    && array_udiff($ancestorCollection->sources, $collection->sources, $comparator) === []
                ) {
                    $purged = true;
                    unset($directives[$directive]);
                    continue 2;
                }
            }
        }
        foreach ($directives as $directive => $collection) {
            // applies implicit changes to sources in case 'strict-dynamic' is used
            if ($collection->contains(SourceKeyword::strictDynamic)) {
                $directives[$directive] = SourceKeyword::strictDynamic->applySourceImplications($collection) ?? $collection;
            }
        }
        if (!$purged) {
            return $this;
        }
        $target = clone $this;
        $target->directives = $directives;
        return $target;
    }

    /**
     * Compiles this policy and returns the serialized representation to be used as HTTP header value.
     */
    public function compile(): string
    {
        $policyParts = [];
        $service = GeneralUtility::makeInstance(ModelService::class);
        foreach ($this->prepare()->directives as $directive => $collection) {
            $directiveParts = $service->compileSources($this->nonce, $collection);
            if ($directiveParts !== []) {
                array_unshift($directiveParts, $directive->value);
                $policyParts[] = implode(' ', $directiveParts);
            }
        }
        return implode('; ', $policyParts);
    }

    protected function compareSources(
        SourceKeyword|SourceScheme|Nonce|UriValue|RawValue $a,
        SourceKeyword|SourceScheme|Nonce|UriValue|RawValue $b
    ): int {
        $service = GeneralUtility::makeInstance(ModelService::class);
        return $service->serializeSource($a) <=> $service->serializeSource($b);
    }

    protected function asMergedSourceCollection(SourceCollection|SourceKeyword|SourceScheme|Nonce|UriValue|RawValue ...$subjects): SourceCollection
    {
        $collections = array_filter($subjects, static fn ($source) => $source instanceof SourceCollection);
        $sources = array_filter($subjects, static fn ($source) => !$source instanceof SourceCollection);
        if ($sources !== []) {
            $collections[] = new SourceCollection(...$sources);
        }
        $target = new SourceCollection();
        foreach ($collections as $collection) {
            $target = $target->merge($collection);
        }
        return $target;
    }
}
