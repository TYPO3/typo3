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

namespace TYPO3\CMS\Belog\ViewHelpers;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to get a username from a backend user id.
 *
 * ```
 *   <belog:username uid="{logItem.backendUserUid}" />
 * ```
 *
 * @internal
 */
final class UsernameViewHelper extends AbstractViewHelper
{
    public function __construct(
        #[Autowire(service: 'cache.runtime')]
        private readonly FrontendInterface $usernameRuntimeCache
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'Uid of the user', true);
    }

    /**
     * Resolve username from backend user id. Can return empty string if there is no user with that UID.
     */
    public function render(): string
    {
        $uid = $this->arguments['uid'];
        $cacheIdentifier = 'belog-viewhelper-username_' . $uid;
        if ($this->usernameRuntimeCache->has($cacheIdentifier)) {
            return $this->usernameRuntimeCache->get($cacheIdentifier);
        }
        $username = BackendUtility::getRecord('be_users', $uid)['username'] ?? '';
        $this->usernameRuntimeCache->set($cacheIdentifier, $username);
        return $username;
    }
}
