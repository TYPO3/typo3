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
use TYPO3\CMS\Core\Security\Authorization\Policy\PolicyDecisionPointInterface;
use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\Subject\SchemaField;

/**
 * Decides on editing a field:
 * isGranted(Scope::WRITE, SchemaField::fromSchema($schema, 'hidden')).
 * Only exclude fields need a grant of the policy decision point, every
 * other field is granted to backend users. Access to the table is decided
 * separately. A field of a sub-schema, e.g. "tt_content.textmedia", is
 * denied, as grants and restricting voters name the main table: check the
 * field of the main schema.
 *
 * @extends PrincipalVoter<string, SchemaField>
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
#[AsSecurityVoter]
final class FieldVoter extends PrincipalVoter
{
    public function __construct(
        private readonly PolicyDecisionPointInterface $policyDecisionPoint,
    ) {}

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === Scope::WRITE;
    }

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, SchemaField::class, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof SchemaField && $attribute === Scope::WRITE;
    }

    protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool
    {
        if (!$principal->isBackendUser()) {
            $vote?->addReason('Fields are granted to backend users only.');
            return false;
        }
        if (str_contains($subject->schema->getName(), '.')) {
            $vote?->addReason(sprintf('"%s" is a sub-schema, check the field of its main schema.', $subject->schema->getName()));
            return false;
        }
        if (!$subject->field->supportsAccessControl()) {
            return true;
        }
        return $this->policyDecisionPoint->decide(
            $principal,
            $attribute,
            ResourceIdentifier::field($subject->schema->getName(), $subject->field->getName())
        );
    }
}
