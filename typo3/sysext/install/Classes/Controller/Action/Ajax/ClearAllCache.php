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

use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\View\JsonView;

/**
 * Clear Cache
 *
 * This is an ajax wrapper for clearing all cache.
 *
 * @see \TYPO3\CMS\Install\Service\ClearCacheService
 */
class ClearAllCache extends AbstractAjaxAction
{

    /**
     * @var \TYPO3\CMS\Install\View\JsonView
     */
    protected $view;

    /**
     * @param JsonView $view
     */
    public function __construct(JsonView $view = null)
    {
        $this->view = $view ?: GeneralUtility::makeInstance(JsonView::class);
    }

    /**
     * Initialize the handle action, sets up fluid stuff and assigns default variables.
     * @ToDo Refactor View Initialization for all Ajax Controllers
     */
    protected function initializeHandle()
    {
        // empty on purpose because AbstractAjaxAction still overwrites $this->view with StandaloneView
    }

    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction()
    {
        $statusMessages[] = $this->clearAllCache();
        $statusMessages[] = $this->clearOpcodeCache();

        $this->view->assignMultiple([
            'success' => true,
            'status' => $statusMessages,
        ]);
        return $this->view->render();
    }

    /**
     * Clear all caches
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function clearAllCache()
    {
        /** @var \TYPO3\CMS\Install\Service\ClearCacheService $clearCacheService */
        $clearCacheService = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Service\ClearCacheService::class);
        $clearCacheService->clearAll();
        $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\OkStatus::class);
        $message->setTitle('Successfully cleared all caches');
        return $message;
    }

    /**
     * Clear PHP opcode cache
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function clearOpcodeCache()
    {
        GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive();
        $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\OkStatus::class);
        $message->setTitle('Successfully cleared all available opcode caches');
        return $message;
    }
}
