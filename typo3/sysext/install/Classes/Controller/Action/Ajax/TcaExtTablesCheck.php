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
     * Load base TCA, then load each single ext_tables.php file and see if TCA changed.
     *
     * @return string "OK" on success, the error message otherwise
     */
    protected function executeAction()
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
        if (empty($messages)) {
            $messages[] = 'OK';
        }
        return implode('<br />', $messages);
    }
}
