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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Middleware;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\Behavior;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Disposition;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Type\Map;

/**
 * @internal
 */
final class PolicyBag
{
    private Map $policyMap;

    public function __construct(
        public readonly Scope $scope,
        public readonly Map $dispositionMap,
        public readonly Behavior $behavior,
        public readonly ConsumableNonce $nonce,
    ) {
        $this->policyMap = new Map();
    }

    public function hasPolicies(): bool
    {
        return count($this->policyMap) !== 0;
    }

    public function hasPolicy(Disposition $disposition): bool
    {
        return isset($this->policyMap[$disposition]);
    }

    public function getPolicy(Disposition $disposition): Policy
    {
        return $this->policyMap[$disposition];
    }

    public function setPolicy(Disposition $disposition, Policy $policy): void
    {
        if (isset($this->policyMap[$disposition])) {
            throw new \LogicException('Policy already set', 1646348401);
        }
        $this->policyMap[$disposition] = $policy;
    }
}
