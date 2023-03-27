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

namespace TYPO3\CMS\Reactions\Authentication;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Reactions\Model\ReactionInstruction;

/**
 * TYPO3 backend user authentication for webhooks
 * Auto-logs in, only allowed in webhooks context
 *
 * @internal not part of TYPO3 Core API as this part is experimental
 */
class ReactionUserAuthentication extends BackendUserAuthentication
{
    public $dontSetCookie = true;
    protected ?ReactionInstruction $reactionInstruction = null;

    public function setReactionInstruction(ReactionInstruction $reactionInstruction): void
    {
        $this->reactionInstruction = $reactionInstruction;
        if ($reactionInstruction->getImpersonateUser()) {
            $this->setBeUserByUid($reactionInstruction->getImpersonateUser());
        }
    }

    public function start(ServerRequestInterface $request)
    {
        if (empty($this->user['uid'])) {
            return;
        }
        $this->unpack_uc();
        // The groups are fetched and ready for permission checking in this initialization.
        $this->fetchGroupData();
        $this->backendSetUC();
    }

    /**
     * Replacement for AbstractUserAuthentication::checkAuthentication()
     *
     * Not required in WebHook mode if no user is impersonated, therefore empty.
     */
    public function checkAuthentication(ServerRequestInterface $request)
    {
        // do nothing
    }

    public function getOriginalUserIdWhenInSwitchUserMode(): ?int
    {
        return null;
    }

    public function backendCheckLogin(ServerRequestInterface $request = null): void
    {
        // do nothing
    }

    /**
     * Determines whether a webhook backend user is allowed to access TYPO3.
     * Only when adminOnly is off (=0)
     *
     * @internal
     */
    public function isUserAllowedToLogin(): bool
    {
        return (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] === 0;
    }

    public function initializeBackendLogin(ServerRequestInterface $request = null): void
    {
        throw new \RuntimeException('Login Error: No login possible for reaction.', 1669800914);
    }
}
