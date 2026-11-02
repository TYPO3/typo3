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

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Security\Authorization\BackendMountContext;
use TYPO3\CMS\Core\Security\Authorization\LegacyBackendUserRegistry;
use TYPO3\CMS\Core\Security\Authorization\Page\PageAccessContext;
use TYPO3\CMS\Core\Security\Authorization\Page\PagePermissionEvaluator;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Security\Authorization\PrincipalUser;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * Decides on the page permissions of a backend user on a page, given as Page.
 * Scope::READ grants the page as BackendUtility::readPageAccess() does for its
 * uid: the page and the pages up to its webmount are shown to the user. For a
 * page overlaid with a workspace version or a translation, the record stored
 * under its uid decides, not the settings merged in by the overlay.
 * Scope::UPDATE, Scope::DELETE, Scope::CREATE and Scope::UPDATE_CONTENT decide
 * the permission bits of calcPerms() to edit the page, to delete it, to create
 * subpages and to change its content.
 *
 * Whether a change is allowed also depends on checks not taken here, such as
 * the editlock of the page, the table permissions, the languages and the
 * workspace of the user.
 *
 * The page must lie within a webmount of the mounts stored on the token, or of
 * the backend user object the principal was built from. A token without either,
 * such as the one of isGrantedForUser() with a principal not built from a user
 * object, is denied for users who are not administrators.
 *
 * @extends Voter<string, Page>
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
#[AsSecurityVoter]
final class PageVoter extends Voter
{
    private const array PERMISSIONS = [
        Scope::READ => Permission::PAGE_SHOW,
        Scope::UPDATE => Permission::PAGE_EDIT,
        Scope::DELETE => Permission::PAGE_DELETE,
        Scope::CREATE => Permission::PAGE_NEW,
        Scope::UPDATE_CONTENT => Permission::CONTENT_EDIT,
    ];

    public function __construct(
        private readonly PagePermissionEvaluator $pagePermissionEvaluator,
        private readonly LegacyBackendUserRegistry $legacyUserRegistry,
    ) {}

    public function supportsAttribute(string $attribute): bool
    {
        return isset(self::PERMISSIONS[$attribute]);
    }

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, Page::class, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Page && isset(self::PERMISSIONS[$attribute]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof PrincipalUser || !$user->principal->isBackendUser()) {
            $vote?->addReason('Pages are decided for backend users only.');
            return false;
        }
        $context = PageAccessContext::fromPrincipal(
            $user->principal,
            BackendMountContext::fromToken($token, $this->legacyUserRegistry),
            $this->legacyUserRegistry->getUser($user->principal),
        );
        $record = $subject->getRawRecord()->toArray();
        // An overlaid page is passed as the overlay methods return it
        $computedProperties = $subject->getComputedProperties();
        if ($computedProperties->getVersionedUid() !== null) {
            $record['_ORIG_uid'] = $computedProperties->getVersionedUid();
        }
        if ($computedProperties->getLocalizedUid() !== null) {
            $record['_LOCALIZED_UID'] = $computedProperties->getLocalizedUid();
        }
        if ($attribute === Scope::READ) {
            return $this->pagePermissionEvaluator->isShown($context, $record);
        }
        $permission = self::PERMISSIONS[$attribute];
        return ($this->pagePermissionEvaluator->calculate($context, $record) & $permission) === $permission;
    }
}
