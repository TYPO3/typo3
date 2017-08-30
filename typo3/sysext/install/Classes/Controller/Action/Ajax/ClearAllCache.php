<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\ClearCacheService;

/**
 * Clear Cache
 *
 * This is an ajax wrapper for clearing all cache.
 */
class ClearAllCache extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        GeneralUtility::makeInstance(ClearCacheService::class)->clearAll();
        GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive();

        $messageQueue = (new FlashMessageQueue('install'))->enqueue(
            new FlashMessage('Successfully cleared all caches and all available opcode caches.')
        );

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messageQueue,
        ]);
        return $this->view->render();
    }
}
