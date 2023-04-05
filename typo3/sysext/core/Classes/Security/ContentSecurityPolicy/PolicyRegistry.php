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

/**
 * A shared service registry to hold additional adjustments that were collected during
 * processing the current request. For instance, it would be used to temporarily(!) allow
 * a particular CSP URL/aspect.
 *
 * @internal
 */
final class PolicyRegistry
{
    /**
     * @var list<MutationCollection>
     */
    private array $mutationCollections = [];

    public function appendMutationCollection(MutationCollection $collection): void
    {
        $this->mutationCollections[] = $collection;
    }

    /**
     * @return list<MutationCollection>
     */
    public function getMutationCollections(): array
    {
        return $this->mutationCollections;
    }

    public function setMutationsCollections(MutationCollection ...$collections): void
    {
        $this->mutationCollections = $collections;
    }

    public function hasMutationCollections(): bool
    {
        return $this->mutationCollections !== [];
    }
}
