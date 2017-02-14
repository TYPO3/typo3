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
use TYPO3\CMS\Install\Service\LoadTcaService;
use TYPO3\CMS\Install\Status\NoticeStatus;
use TYPO3\CMS\Install\Status\StatusInterface;
use TYPO3\CMS\Install\View\JsonView;

/**
 * Check ext_tables.php files of loaded extensions for TCA changes.
 *
 * Changing TCA in ext_tables is highly discouraged since core version 7
 * and can break the frontend since core version 8.
 *
 * This test loads all ext_tables.php one-by-one and finds files that
 * still change TCA.
 */
class TcaExtTablesCheck extends AbstractAjaxAction
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
     * Fetches all installed extensions that still mess with the TCA in a way they shouldn't
     *
     * @return array status list of extensions that still mess with the TCA
     */
    protected function executeAction()
    {
        $statusMessages = [];
        $tcaMessages = $this->checkTcaChangesInExtTables();

        foreach ($tcaMessages as $tcaMessage) {
            /** @var $message StatusInterface */
            $message = GeneralUtility::makeInstance(NoticeStatus::class);
            $message->setTitle($tcaMessage);
            $statusMessages[] = $message;
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $statusMessages,
        ]);
        return $this->view->render();
    }

    /**
     * Load base TCA, then load each single ext_tables.php file and see if TCA changed.
     *
     * @return array list of extensions that still mess with the tca
     */
    protected function checkTcaChangesInExtTables()
    {
        $loadTcaService = GeneralUtility::makeInstance(LoadTcaService::class);
        $loadTcaService->loadExtensionTablesWithoutMigration();
        $baseTca = $GLOBALS['TCA'];
        $messages = [];
        foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $extensionKey => $extensionInformation) {
            if ((is_array($extensionInformation) || $extensionInformation instanceof \ArrayAccess)
                && $extensionInformation['ext_tables.php']
            ) {
                $loadTcaService->loadSingleExtTablesFile($extensionKey);
                $newTca = $GLOBALS['TCA'];
                if ($newTca !== $baseTca) {
                    $messages[] = $extensionKey;
                }
                $baseTca = $newTca;
            }
        }
        return $messages;
    }
}
