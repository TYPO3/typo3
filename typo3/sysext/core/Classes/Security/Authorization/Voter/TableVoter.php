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

namespace TYPO3\CMS\Core\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Security\Authorization\Policy\PolicyDecisionPointInterface;
use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\SecurityRole;

/**
 * Decides on reading and changing records of a table, given as TcaSchema:
 * isGranted(Scope::WRITE, $tcaSchemaFactory->get('tt_content')).
 * Scope::WRITE stands for creating, updating and deleting records alike.
 * A sub-schema, e.g. "tt_content.textmedia", is denied, as grants and
 * restricting voters name the main table: check the main schema.
 * Principals of frontend users are denied.
 * Tables marked "adminOnly" are granted to admins, and to other backend
 * users only by an AdminOnlyTableGrantInterface, never by a grant of a
 * group. Any other table is decided by the policy decision point.
 *
 * @extends PrincipalVoter<string, TcaSchema>
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
#[AsSecurityVoter]
final class TableVoter extends PrincipalVoter
{
    private const array SCOPES = [Scope::READ, Scope::WRITE];

    public function __construct(
        private readonly PolicyDecisionPointInterface $policyDecisionPoint,
    ) {}

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, self::SCOPES, true);
    }

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, TcaSchema::class, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof TcaSchema
            && in_array($attribute, self::SCOPES, true);
    }

    protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool
    {
        if (!$principal->isBackendUser()) {
            $vote?->addReason('Tables are granted to backend users only.');
            return false;
        }
        if (str_contains($subject->getName(), '.')) {
            $vote?->addReason(sprintf('"%s" is a sub-schema, check its main schema.', $subject->getName()));
            return false;
        }
        if ($subject->hasCapability(TcaSchemaCapability::AccessAdminOnly)) {
            return $this->hasRole($principal, SecurityRole::ADMIN)
                || $this->policyDecisionPoint->decide($principal, $attribute, ResourceIdentifier::adminOnlyTable($subject->getName()));
        }
        return $this->policyDecisionPoint->decide($principal, $attribute, ResourceIdentifier::table($subject->getName()));
    }
}
