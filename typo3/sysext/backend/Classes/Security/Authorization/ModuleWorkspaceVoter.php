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
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
use TYPO3\CMS\Core\Security\Authorization\LegacyBackendUserRegistry;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\PrincipalUser;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Denies accessing a backend module that is not available in the current
 * workspace of the backend user, see the "workspaces" option of a module:
 * "live" restricts a module to the live workspace, "offline" to the other
 * workspaces, "*" makes it available in every workspace, even without
 * workspace (-99). A module without the option inherits it from its parent.
 * The restriction only applies with EXT:workspaces loaded.
 *
 * The voter never grants, it abstains for a module available in the
 * workspace, so that the decision on its access value is left to the other
 * voters. The workspace is the one of the backend user object the principal
 * was built from; without such an object the voter abstains as well.
 *
 * ModuleProvider::accessGranted() ignores the restriction on request by
 * setting the token attribute IGNORE_WORKSPACE_RESTRICTION.
 *
 * @internal
 */
#[AsSecurityVoter]
final readonly class ModuleWorkspaceVoter implements CacheableVoterInterface
{
    /**
     * Token attribute to decide the access to a module regardless of the workspace
     */
    public const string IGNORE_WORKSPACE_RESTRICTION = 'typo3.module.ignoreWorkspaceRestriction';

    public function __construct(
        private LegacyBackendUserRegistry $legacyUserRegistry,
    ) {}

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === Scope::ACCESS;
    }

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, ModuleInterface::class, true);
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
    {
        if (!$subject instanceof ModuleInterface
            || !in_array(Scope::ACCESS, $attributes, true)
            || ($token->hasAttribute(self::IGNORE_WORKSPACE_RESTRICTION) && $token->getAttribute(self::IGNORE_WORKSPACE_RESTRICTION) === true)
        ) {
            return self::ACCESS_ABSTAIN;
        }
        $principalUser = $token->getUser();
        $user = $principalUser instanceof PrincipalUser ? $this->legacyUserRegistry->getUser($principalUser->principal) : null;
        if ($user === null || self::isAvailableInWorkspace($subject, $user->workspace)) {
            return self::ACCESS_ABSTAIN;
        }
        $vote?->addReason(sprintf('The module "%s" is not available in the workspace %d.', $subject->getIdentifier(), $user->workspace));
        return self::ACCESS_DENIED;
    }

    /**
     * Whether the module is available in the given workspace, the one
     * place deciding the workspace restriction of modules
     */
    public static function isAvailableInWorkspace(ModuleInterface $module, int $workspace): bool
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces')) {
            return true;
        }
        $workspaceAccess = $module->getWorkspaceAccess();
        if ($workspaceAccess === '' && $module->hasParentModule()) {
            // In case workspace access is not set explicitly use the information
            // from the parent module as this access restriction is inherited.
            $workspaceAccess = $module->getParentModule()->getWorkspaceAccess();
        }
        if ($workspaceAccess !== '' && $workspaceAccess !== '*') {
            if (($workspaceAccess === 'live' && $workspace !== 0)
                || ($workspaceAccess === 'offline' && $workspace === 0)
            ) {
                return false;
            }
        } elseif ($workspace === -99 && $workspaceAccess !== '*') {
            return false;
        }
        return true;
    }
}
