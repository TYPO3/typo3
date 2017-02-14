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

use TYPO3\CMS\Core\Migrations\TcaMigration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\LoadTcaService;
use TYPO3\CMS\Install\Status\NoticeStatus;
use TYPO3\CMS\Install\Status\StatusInterface;
use TYPO3\CMS\Install\View\JsonView;

/**
 * Checks whether the current TCA needs migrations and displays applied migrations.
 */
class TcaMigrationsCheck extends AbstractAjaxAction
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
     * Load all TCA Migrations and return if there are any todos
     *
     * @return array TCA status messages
     */
    protected function executeAction()
    {
        $statusMessages = [];
        $tcaMessages = $this->checkTcaMigrations();

        foreach ($tcaMessages as $tcaMessage) {
            /** @var $message StatusInterface */
            $message = GeneralUtility::makeInstance(NoticeStatus::class);
            $message->setMessage($tcaMessage);
            $statusMessages[] = $message;
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $statusMessages,
        ]);
        return $this->view->render();
    }

    /**
     * "TCA migration" action
     *
     * @return array The TCA migration messages
     */
    protected function checkTcaMigrations()
    {
        GeneralUtility::makeInstance(LoadTcaService::class)->loadExtensionTablesWithoutMigration();
        $tcaMigration = GeneralUtility::makeInstance(TcaMigration::class);
        $GLOBALS['TCA'] = $tcaMigration->migrate($GLOBALS['TCA']);
        return $tcaMigration->getMessages();
    }
}
