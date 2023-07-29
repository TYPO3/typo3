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
use TYPO3\CMS\Core\Type\Map;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Representation of the whole Content-Security-Policy
 * see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy
 *
 * @internal This implementation still might be adjusted
 */
class Policy
{
    /**
     * @var Map<Directive, SourceCollection>
     */
    protected Map $directives;

    /**
     * @param SourceCollection|SourceInterface ...$sources (optional) default-src sources
     */
    public function __construct(SourceCollection|SourceInterface ...$sources)
    {
        $this->directives = new Map();
        $directive = Directive::DefaultSrc;
        $collection = $this->asMergedSourceCollection(...$sources);
        $collection = $this->purgeNonApplicableSources($directive, $collection);
        if (!$collection->isEmpty()) {
            $this->directives[$directive] = $collection;
        }
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
            } elseif ($mutation->mode === MutationMode::Append) {
                $self = $self->append($mutation->directive, ...$mutation->sources);
            } elseif ($mutation->mode === MutationMode::InheritOnce) {
                $self = $self->inherit($mutation->directive);
            } elseif ($mutation->mode === MutationMode::InheritAgain) {
                $self = $self->inherit($mutation->directive, true);
            } elseif ($mutation->mode === MutationMode::Extend) {
                $self = $self->extend($mutation->directive, ...$mutation->sources);
            } elseif ($mutation->mode === MutationMode::Reduce) {
                $self = $self->reduce($mutation->directive, ...$mutation->sources);
            } elseif ($mutation->mode === MutationMode::Remove) {
                $self = $self->remove($mutation->directive);
            }
        }
        return $self;
    }

    /**
     * Sets (overrides) the 'default-src' directive, which is also the fall-back for other more specific directives.
     */
    public function default(SourceCollection|SourceInterface ...$sources): self
    {
        return $this->set(Directive::DefaultSrc, ...$sources);
    }

    /**
     * Appends to an existing directive, or a new source collection in case it was empty.
     */
    public function append(Directive $directive, SourceCollection|SourceInterface ...$sources): self
    {
        $collection = $this->asMergedSourceCollection(...$sources);
        $collection = $this->purgeNonApplicableSources($directive, $collection);
        if ($collection->isEmpty() && !$directive->isStandAlone()) {
            return $this;
        }
        $targetCollection = $this->asMergedSourceCollection(...array_filter([
            $this->directives[$directive] ?? null,
            $collection,
        ]));
        return $this->changeDirectiveSources($directive, $targetCollection);
    }

    /**
     * Inherits the current source collection of the closest non-empty ancestor in the chain.
     *
     * @param bool $again whether to inherit again and merge with the existing source collection
     */
    public function inherit(Directive $directive, bool $again = false): self
    {
        $currentSources = $this->directives[$directive] ?? null;
        if ($again || $currentSources === null) {
            foreach ($directive->getAncestors() as $ancestorDirective) {
                if ($this->has($ancestorDirective)) {
                    $ancestorCollection = $this->directives[$ancestorDirective];
                    break;
                }
            }
        }
        $targetCollection = $this->asMergedSourceCollection(...array_filter([
            $ancestorCollection ?? null,
            $currentSources,
        ]));
        return $this->changeDirectiveSources($directive, $targetCollection);
    }

    /**
     * Extends a specific directive, either by appending sources or by inheriting from an ancestor directive.
     */
    public function extend(Directive $directive, SourceCollection|SourceInterface ...$sources): self
    {
        return $this->inherit($directive)->append($directive, ...$sources);
    }

    public function reduce(Directive $directive, SourceCollection|SourceInterface ...$sources): self
    {
        if (!$this->has($directive)) {
            return $this;
        }
        $collection = $this->asMergedSourceCollection(...$sources);
        $targetCollection = $this->directives[$directive]->exclude($collection);
        return $this->changeDirectiveSources($directive, $targetCollection);
    }

    /**
     * Sets (overrides) a specific directive.
     */
    public function set(Directive $directive, SourceCollection|SourceInterface ...$sources): self
    {
        $collection = $this->asMergedSourceCollection(...$sources);
        $collection = $this->purgeNonApplicableSources($directive, $collection);
        return $this->changeDirectiveSources($directive, $collection);
    }

    /**
     * Removes a specific directive.
     */
    public function remove(Directive $directive): self
    {
        if (!$this->has($directive)) {
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

    public function has(Directive $directive): bool
    {
        return isset($this->directives[$directive]);
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
            // applies implicit changes to sources in case 'strict-dynamic' is used for applicable directives
            if ($collection->contains(SourceKeyword::strictDynamic) && SourceKeyword::strictDynamic->isApplicable($directive)) {
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
     *
     * @param ConsumableNonce $nonce used to substitute `SourceKeyword::nonceProxy` items during compilation
     * @param ?FrontendInterface $cache to be used for storing compiled CSP aspects (disabled in install tool)
     */
    public function compile(ConsumableNonce $nonce, ?FrontendInterface $cache = null): string
    {
        $policyParts = [];
        $service = GeneralUtility::makeInstance(ModelService::class, $cache);
        foreach ($this->prepare()->directives as $directive => $collection) {
            $directiveParts = $service->compileSources($nonce, $collection);
            if ($directiveParts !== [] || $directive->isStandAlone()) {
                array_unshift($directiveParts, $directive->value);
                $policyParts[] = implode(' ', $directiveParts);
            }
        }
        return implode('; ', $policyParts);
    }

    public function containsDirective(Directive $directive, SourceCollection|SourceInterface ...$sources): bool
    {
        $sources = $this->asMergedSourceCollection(...$sources);
        return (bool)$this->directives[$directive]?->contains(...$sources->sources);
    }

    public function coversDirective(Directive $directive, SourceCollection|SourceInterface ...$sources): bool
    {
        $sources = $this->asMergedSourceCollection(...$sources);
        return (bool)$this->directives[$directive]?->covers(...$sources->sources);
    }

    /**
     * Whether the current policy contains another policy (in terms of instances and values, but without inference).
     */
    public function contains(Policy $other): bool
    {
        if ($other->isEmpty()) {
            return false;
        }
        foreach ($other->directives as $directive => $collection) {
            if (!$this->containsDirective($directive, $collection)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Whether the current policy covers another policy (in terms of CSP inference, considering wildcards and similar).
     */
    public function covers(Policy $other): bool
    {
        if ($other->isEmpty()) {
            return false;
        }
        foreach ($other->directives as $directive => $collection) {
            if (!$this->coversDirective($directive, $collection)) {
                return false;
            }
        }
        return true;
    }

    protected function compareSources(SourceInterface $a, SourceInterface $b): int
    {
        $service = GeneralUtility::makeInstance(ModelService::class);
        return $service->serializeSource($a) <=> $service->serializeSource($b);
    }

    protected function changeDirectiveSources(Directive $directive, SourceCollection $sources): self
    {
        if ($sources->isEmpty() && !$directive->isStandAlone()) {
            return $this;
        }
        $target = clone $this;
        $target->directives[$directive] = $sources;
        return $target;
    }

    protected function asMergedSourceCollection(SourceCollection|SourceInterface ...$subjects): SourceCollection
    {
        $collections = array_filter($subjects, static fn($source) => $source instanceof SourceCollection);
        $sources = array_filter($subjects, static fn($source) => !$source instanceof SourceCollection);
        if ($sources !== []) {
            $collections[] = new SourceCollection(...$sources);
        }
        $target = new SourceCollection();
        foreach ($collections as $collection) {
            $target = $target->merge($collection);
        }
        return $target;
    }

    protected function purgeNonApplicableSources(Directive $directive, SourceCollection $collection): SourceCollection
    {
        $sources = array_filter(
            $collection->sources,
            static fn(SourceInterface $source): bool => $source instanceof SourceKeyword ? $source->isApplicable($directive) : true
        );
        return new SourceCollection(...$sources);
    }
}
