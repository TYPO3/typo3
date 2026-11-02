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

namespace TYPO3\CMS\Core\Security\Authorization;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * The mounts of a backend user in a request: the workspace and the
 * webmounts. Neither belongs to the principal, as the webmounts depend on
 * the workspace and can be changed during a request, e.g. by the mount
 * points of the element browser.
 *
 * The backend stores the mounts as attribute of the PrincipalToken, so that
 * voters decide on the mounts the request started with.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
final readonly class BackendMountContext
{
    public const string TOKEN_ATTRIBUTE = 'typo3.backend_mounts';

    /**
     * @param int $workspaceId the workspace of the user, 0 for live
     * @param list<int> $webmounts uids of the pages mounted for the user, may include 0
     */
    public function __construct(
        public int $workspaceId,
        public array $webmounts,
    ) {}

    /**
     * The mounts stored on the token, or those of the backend user object the
     * principal of the token was built from, no mounts otherwise.
     */
    public static function fromToken(TokenInterface $token, LegacyBackendUserRegistry $legacyUserRegistry): self
    {
        $mounts = $token->hasAttribute(self::TOKEN_ATTRIBUTE) ? $token->getAttribute(self::TOKEN_ATTRIBUTE) : null;
        if ($mounts instanceof self) {
            return $mounts;
        }
        $user = $token->getUser();
        $backendUser = $user instanceof PrincipalUser ? $legacyUserRegistry->getUser($user->principal) : null;
        return $backendUser !== null ? self::fromBackendUser($backendUser) : new self(0, []);
    }

    /**
     * Before the workspace of the user is initialized, the user works in live.
     */
    public static function fromBackendUser(BackendUserAuthentication $backendUser): self
    {
        return new self(
            max(0, $backendUser->workspace),
            array_values(array_map(intval(...), $backendUser->getWebmounts())),
        );
    }
}
