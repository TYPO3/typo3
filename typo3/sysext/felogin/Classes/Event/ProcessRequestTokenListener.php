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

namespace TYPO3\CMS\FrontendLogin\Event;

use TYPO3\CMS\Core\Authentication\Event\BeforeRequestTokenProcessedEvent;
use TYPO3\CMS\Core\Security\RequestToken;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Process request token.
 */
final class ProcessRequestTokenListener
{
    public function __invoke(BeforeRequestTokenProcessedEvent $event): void
    {
        $user = $event->getUser();
        $requestToken = $event->getRequestToken();
        if (!$user instanceof FrontendUserAuthentication || !$requestToken instanceof RequestToken) {
            return;
        }
        $pidParam = (string)($requestToken->params['pid'] ?? '');
        if ($user->checkPid) {
            $user->checkPid_value = $pidParam;
        }
    }
}
