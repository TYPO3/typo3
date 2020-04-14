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

namespace TYPO3\CMS\Backend\Authentication\Event;

/**
 * This event is triggered when a "SU" (switch user) action has been triggered
 */
final class SwitchUserEvent
{
    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var array
     */
    private $targetUser;

    /**
     * @var array
     */
    private $currentUser;

    public function __construct(string $sessionId, array $targetUser, array $currentUser)
    {
        $this->sessionId = $sessionId;
        $this->targetUser = $targetUser;
        $this->currentUser = $currentUser;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getTargetUser(): array
    {
        return $this->targetUser;
    }

    public function getCurrentUser(): array
    {
        return $this->currentUser;
    }
}
