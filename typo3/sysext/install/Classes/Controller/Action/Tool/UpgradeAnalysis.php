<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\Action\AbstractAction;
use TYPO3\CMS\Install\UpgradeAnalysis\DocumentationFile;

/**
 * Run code analysis based on changelog documentation
 */
class UpgradeAnalysis extends AbstractAction
{

    /**
     * Executes the action upon click in the Install Tool Menu
     *
     * All available documentation files are aggregated and
     * passed to the frontend to be displayed as a list of entries.
     *
     * All following actions are handled via Ajax.
     *
     * @return string Rendered content
     * @throws \InvalidArgumentException
     */
    protected function executeAction()
    {
        $documentationFileService = new DocumentationFile();
        $documentationFiles = $documentationFileService->findDocumentationFiles(
            strtr(realpath(PATH_site . ExtensionManagementUtility::siteRelPath('core') . 'Documentation/Changelog'), '\\', '/')
        );

        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $saveIgnoredItemsToken = $formProtection->generateToken('installTool', 'saveIgnoredItems');
        $removeIgnoredItemsToken = $formProtection->generateToken('installTool', 'removeIgnoredItems');
        $this->view->assignMultiple([
            'saveIgnoredItemsToken' => $saveIgnoredItemsToken,
            'removeIgnoredItemsToken' => $removeIgnoredItemsToken,
        ]);
        $documentationFiles = array_reverse($documentationFiles);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_registry');
        $filesMarkedAsRead = $queryBuilder
            ->select('*')
            ->from('sys_registry')
            ->where(
                $queryBuilder->expr()->eq(
                    'entry_namespace', $queryBuilder->createNamedParameter('upgradeAnalysisIgnoredFiles', \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();

        $hashesMarkedAsRead = [];
        foreach ($filesMarkedAsRead as $file) {
            $hashesMarkedAsRead[] = $file['entry_key'];
        }

        $fileMarkedAsNotAffected = $queryBuilder
            ->select('*')
            ->from('sys_registry')
            ->where(
                $queryBuilder->expr()->eq(
                    'entry_namespace', $queryBuilder->createNamedParameter('extensionScannerNotAffected', \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();

        $hashesMarkedAsNotAffected = [];
        foreach ($fileMarkedAsNotAffected as $file) {
            $hashesMarkedAsNotAffected[] = $file['entry_key'];
        }

        $readFiles = [];
        foreach ($documentationFiles as $section => &$files) {
            foreach ($files as $fileId => $fileData) {
                if (in_array($fileData['file_hash'], $hashesMarkedAsRead, true)) {
                    $fileData['section'] = $section;
                    $readFiles[$fileId] = $fileData;
                    unset($files[$fileId]);
                }
            }
        }

        $notAffectedFiles = [];
        foreach ($documentationFiles as $section => &$files) {
            foreach ($files as $fileId => $fileData) {
                if (in_array($fileData['file_hash'], $hashesMarkedAsNotAffected, true)) {
                    $fileData['section'] = $section;
                    $notAffectedFiles[$fileId] = $fileData;
                    unset($files[$fileId]);
                }
            }
        }

        $this->view->assign('files', $documentationFiles);
        $this->view->assign('shownFiles', $readFiles);
        $this->view->assign('notAffectedFiles', $notAffectedFiles);
        return $this->view->render();
    }
}
