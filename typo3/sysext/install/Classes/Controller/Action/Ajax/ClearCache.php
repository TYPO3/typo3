<?php
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Clear Cache
 *
 * This is an ajax wrapper for clearing the cache. Used for example
 * after uninstalling an extension via ajax.
 *
 * @see \TYPO3\CMS\Install\Service\ClearCacheService
 */
class ClearCache extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return string Rendered content
     */
    protected function executeAction()
    {
        /** @var \TYPO3\CMS\Install\Service\ClearCacheService $clearCacheService */
        $clearCacheService = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Service\ClearCacheService::class);
        $clearCacheService->clearAll();
        return 'OK';
    }
}
