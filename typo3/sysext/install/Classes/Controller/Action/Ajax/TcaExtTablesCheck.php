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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\LoadTcaService;

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
     * Fetches all installed extensions that still mess with the TCA in a way they shouldn't
     *
     * @return array status list of extensions that still mess with the TCA
     */
    protected function executeAction(): array
    {
        $messageQueue = new FlashMessageQueue('install');
        $tcaMessages = $this->checkTcaChangesInExtTables();
        foreach ($tcaMessages as $tcaMessage) {
            $messageQueue->enqueue(new FlashMessage(
                '',
                $tcaMessage,
                FlashMessage::NOTICE
            ));
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messageQueue,
        ]);
        return $this->view->render();
    }

    /**
     * Load base TCA, then load each single ext_tables.php file and see if TCA changed.
     *
     * @return array list of extensions that still mess with the tca
     */
    protected function checkTcaChangesInExtTables(): array
    {
        $loadTcaService = GeneralUtility::makeInstance(LoadTcaService::class);
        $loadTcaService->loadExtensionTablesWithoutMigration();
        $baseTca = $GLOBALS['TCA'];
        $extensions = [];
        foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $extensionKey => $extensionInformation) {
            if ((is_array($extensionInformation) || $extensionInformation instanceof \ArrayAccess)
                && $extensionInformation['ext_tables.php']
            ) {
                $loadTcaService->loadSingleExtTablesFile($extensionKey);
                $newTca = $GLOBALS['TCA'];
                if ($newTca !== $baseTca) {
                    $extensions[] = $extensionKey;
                }
                $baseTca = $newTca;
            }
        }
        return $extensions;
    }
}
