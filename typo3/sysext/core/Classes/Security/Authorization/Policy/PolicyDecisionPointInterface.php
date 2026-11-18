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

namespace TYPO3\CMS\Core\Security\Authorization\Policy;

use TYPO3\CMS\Core\Authentication\Principal;

/**
 * The policy decision point (PDP) answers one question for the Core voters:
 * does a grant of the principal cover this scope on this resource? It knows
 * where grants are stored, the voters know when a grant is needed at all,
 * for example for a module with the access "admin".
 *
 * A PDP only knows the resource types and scopes it has grants for, see
 * supports(). For those, decide() answers allow or deny, never abstain.
 * Voters ask supports() first and abstain otherwise, so that a voter of an
 * extension decides on resource types of its own.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
interface PolicyDecisionPointInterface
{
    /**
     * Whether grants for the scope on the type of the resource exist at all.
     */
    public function supports(string $scope, ResourceIdentifier $resource): bool;

    /**
     * Whether a grant of the principal covers the scope on the resource,
     * false for a scope or resource type the PDP does not support.
     */
    public function decide(Principal $principal, string $scope, ResourceIdentifier $resource): bool;
}
