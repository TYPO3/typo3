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

namespace TYPO3\CMS\Dashboard\Security\Authorization;

use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\Authorization\Policy\PolicyDecisionPointInterface;
use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\SecurityRole;
use TYPO3\CMS\Core\Security\Authorization\Voter\PrincipalVoter;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfiguration;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;

/**
 * Decides on using a dashboard widget:
 * isGranted(Scope::ACCESS, $widgetRegistry->getAllWidgets()['sysLogErrors']).
 * A widget marked "adminOnly" is granted to admins only, every other widget
 * by a grant of the policy decision point. Widgets are granted to backend
 * users only.
 *
 * @extends PrincipalVoter<string, WidgetConfigurationInterface>
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
#[AsSecurityVoter]
final class WidgetVoter extends PrincipalVoter
{
    public function __construct(
        private readonly PolicyDecisionPointInterface $policyDecisionPoint,
    ) {}

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === Scope::ACCESS;
    }

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, WidgetConfigurationInterface::class, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof WidgetConfigurationInterface && $attribute === Scope::ACCESS;
    }

    protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool
    {
        if (!$principal->isBackendUser()) {
            $vote?->addReason('Dashboard widgets are granted to backend users only.');
            return false;
        }
        if ($subject instanceof WidgetConfiguration && $subject->isAdminOnly() && !$this->hasRole($principal, SecurityRole::ADMIN)) {
            $vote?->addReason(sprintf('The widget "%s" is granted to admins only.', $subject->getIdentifier()));
            return false;
        }
        return $this->policyDecisionPoint->decide($principal, $attribute, ResourceIdentifier::widget($subject->getIdentifier()));
    }
}
