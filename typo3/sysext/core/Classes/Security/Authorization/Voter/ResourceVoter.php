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
use TYPO3\CMS\Core\Security\Authorization\SecurityRole;

/**
 * Hands a ResourceIdentifier subject to the policy decision point as it
 * is, with the attribute as scope, e.g. for page types:
 * isGranted(Scope::WRITE, ResourceIdentifier::pageType(254)).
 *
 * It abstains on resource types and scopes the policy decision point has no
 * grants for, so that a voter of an extension decides on a resource type of
 * its own: isGranted(Scope::ACCESS, new ResourceIdentifier('tx_shop_discount', 'staff')).
 * Of the types the policy decision point has grants for, only those without
 * an object of their own are decided as identifier. Every other type, e.g.
 * a table, a field, a module or a widget, is denied, as it is checked on
 * its TcaSchema, SchemaField, ModuleInterface or WidgetConfigurationInterface
 * so that "adminOnly", exclude fields and access values apply, and a voter
 * of an extension granting an identifier cannot bypass them. It abstains on
 * languages, which the LanguageVoter decides.
 *
 * Values of authMode fields follow BackendUserAuthentication::checkAuthMode():
 * a blank value and "--div--" need no grant, a value containing ":", "|" or
 * "," is granted to admins only. An identifier without table, field and
 * value, or naming a sub-schema such as "tt_content.textmedia", is denied.
 *
 * @extends PrincipalVoter<string, ResourceIdentifier>
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
#[AsSecurityVoter]
final class ResourceVoter extends PrincipalVoter
{
    /**
     * Resource types without an object of their own, decided as identifier
     */
    private const array IDENTIFIER_TYPES = [
        ResourceIdentifier::FIELD_VALUE,
        ResourceIdentifier::PAGE_TYPE,
        ResourceIdentifier::MFA_PROVIDER,
        ResourceIdentifier::CUSTOM_OPTION,
    ];

    public function __construct(
        private readonly PolicyDecisionPointInterface $policyDecisionPoint,
    ) {}

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, ResourceIdentifier::class, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof ResourceIdentifier || $subject->type === ResourceIdentifier::LANGUAGE) {
            return false;
        }
        return $this->policyDecisionPoint->supports($attribute, $subject);
    }

    protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool
    {
        if (!in_array($subject->type, self::IDENTIFIER_TYPES, true)) {
            $vote?->addReason(sprintf('The resource type "%s" is decided on its object only.', $subject->type));
            return false;
        }
        if ($subject->type === ResourceIdentifier::FIELD_VALUE && $principal->isBackendUser()) {
            $parts = explode(':', $subject->identifier, 3);
            if (count($parts) !== 3 || $parts[0] === '' || $parts[1] === '' || str_contains($parts[0], '.')) {
                return false;
            }
            $value = $parts[2];
            if ($value === '' || $value === '--div--') {
                return true;
            }
            if (preg_match('/[:|,]/', $value) === 1) {
                return $this->hasRole($principal, SecurityRole::ADMIN);
            }
        }
        return $this->policyDecisionPoint->decide($principal, $attribute, $subject);
    }
}
