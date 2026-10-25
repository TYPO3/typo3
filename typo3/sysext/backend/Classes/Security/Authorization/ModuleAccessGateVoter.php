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

namespace TYPO3\CMS\Backend\Security\Authorization;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use TYPO3\CMS\Backend\Module\ModuleAccessGateRegistry;
use TYPO3\CMS\Backend\Module\ModuleAccessResult;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
use TYPO3\CMS\Core\Security\Authorization\LegacyBackendUserRegistry;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\PrincipalUser;

/**
 * Decides on accessing a backend module by asking the module access gate
 * registered for its access value: for an access value that is not one of
 * TYPO3 Core, and for one whose Core gate an extension replaced. This keeps
 * the deprecated gates of extensions working, while every module access is
 * decided by the authorization checker, so that voters of extensions can
 * restrict it as well.
 *
 * A gate requires the backend user object the principal was established
 * for. Without it, e.g. for a principal of a token, access is denied.
 *
 * @extends Voter<string, ModuleInterface>
 * @internal
 */
#[AsSecurityVoter]
final class ModuleAccessGateVoter extends Voter
{
    public function __construct(
        private readonly ModuleAccessGateRegistry $gateRegistry,
        private readonly LegacyBackendUserRegistry $legacyUserRegistry,
        private readonly ReplacedModuleAccessGates $replacedGates,
    ) {}

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === Scope::ACCESS;
    }

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, ModuleInterface::class, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof ModuleInterface
            && $attribute === Scope::ACCESS
            && $this->gateRegistry->has($subject->getAccess())
            && (!in_array($subject->getAccess(), ModuleVoter::ACCESS_VALUES, true)
                || $this->replacedGates->isReplaced($subject->getAccess()));
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $principalUser = $token->getUser();
        $user = $principalUser instanceof PrincipalUser ? $this->legacyUserRegistry->getUser($principalUser->principal) : null;
        if ($user === null) {
            $vote?->addReason(sprintf('The module access gate "%s" requires a backend user object.', $subject->getAccess()));
            return false;
        }
        $gate = $this->gateRegistry->get($subject->getAccess());
        trigger_error(
            sprintf(
                'The module access gate "%s" for the access value "%s" is deprecated since TYPO3 v15 and will stop working in TYPO3 v16. Decide the access with a security voter instead.',
                $gate::class,
                $subject->getAccess()
            ),
            E_USER_DEPRECATED
        );
        $result = $gate->decide($subject, $user);
        if ($result !== ModuleAccessResult::Granted) {
            $vote?->addReason(sprintf('The module access gate "%s" did not grant access.', $subject->getAccess()));
        }
        return $result === ModuleAccessResult::Granted;
    }
}
