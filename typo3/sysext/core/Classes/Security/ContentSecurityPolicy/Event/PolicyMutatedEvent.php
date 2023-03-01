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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event;

use Psr\EventDispatcher\StoppableEventInterface;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;

final class PolicyMutatedEvent implements StoppableEventInterface
{
    private bool $stopPropagation = false;
    private Policy $currentPolicy;
    /**
     * @var list<MutationCollection>
     */
    private array $mutationCollections;

    public function __construct(
        public readonly Scope $scope,
        public readonly Policy $defaultPolicy,
        Policy $currentPolicy,
        MutationCollection ...$mutationCollections
    ) {
        $this->currentPolicy = $currentPolicy;
        $this->mutationCollections = $mutationCollections;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopPropagation;
    }

    public function stopPropagation(): void
    {
        $this->stopPropagation = true;
    }

    public function getCurrentPolicy(): Policy
    {
        return $this->currentPolicy;
    }

    public function setCurrentPolicy(Policy $currentPolicy): void
    {
        $this->currentPolicy = $currentPolicy;
    }

    /**
     * @return list<MutationCollection>
     */
    public function getMutationCollections(): array
    {
        return $this->mutationCollections;
    }

    public function setMutationCollections(MutationCollection ...$mutationCollections): void
    {
        $this->mutationCollections = $mutationCollections;
    }
}
