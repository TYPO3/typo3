<?php
declare(strict_types=1);
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

use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\Action\AbstractAction;
use TYPO3\CMS\Install\Service\CoreUpdateService;
use TYPO3\CMS\Install\Service\CoreVersionService;
use TYPO3\CMS\Install\UpgradeAnalysis\DocumentationFile;

/**
 * Render "Upgrade" section main card content
 */
class Upgrade extends AbstractAction
{
    /**
     * Executes the tool
     *
     * @return string Rendered content
     */
    protected function executeAction(): string
    {
        $extensionsInTypo3conf = (new Finder())->directories()->in(PATH_site . 'typo3conf/ext')->depth('== 0')->sortByName();
        $coreUpdateService = GeneralUtility::makeInstance(CoreUpdateService::class);
        $coreVersionService = GeneralUtility::makeInstance(CoreVersionService::class);
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $documentationFiles = $this->getDocumentationFiles();
        $this->view->assignMultiple([
            'extensionCompatibilityTesterProtocolFile' => GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3temp/assets/ExtensionCompatibilityTester.txt',
            'extensionCompatibilityTesterErrorProtocolFile' => GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'typo3temp/assets/ExtensionCompatibilityTesterErrors.json',

            'coreUpdateEnabled' => $coreUpdateService->isCoreUpdateEnabled(),
            'coreUpdateComposerMode' => Bootstrap::usesComposerClassLoading(),
            'coreUpdateIsReleasedVersion' => $coreVersionService->isInstalledVersionAReleasedVersion(),
            'coreUpdateIsSymLinkedCore' => is_link(PATH_site . 'typo3_src'),

            'extensionScannerExtensionList' => $extensionsInTypo3conf,
            'extensionScannerFilesToken' => $formProtection->generateToken('installTool', 'extensionScannerFiles'),
            'extensionScannerScanFileToken' => $formProtection->generateToken('installTool', 'extensionScannerScanFile'),
            'extensionScannerMarkFullyScannedRestFilesToken' => $formProtection->generateToken('installTool', 'extensionScannerMarkFullyScannedRestFiles'),

            'upgradeDocsMarkReadToken' => $formProtection->generateToken('installTool', 'upgradeDocsMarkRead'),
            'upgradeDocsUnmarkReadToken' => $formProtection->generateToken('installTool', 'upgradeDocsUnmarkRead'),
            'upgradeDocsFiles' => $documentationFiles['normalFiles'],
            'upgradeDocsReadFiles' => $documentationFiles['readFiles'],
            'upgradeDocsNotAffectedFiles' => $documentationFiles['notAffectedFiles'],

            'upgradeWizardsMarkUndoneToken' => $formProtection->generateToken('installTool', 'upgradeWizardsMarkUndone'),
            'upgradeWizardsInputToken' => $formProtection->generateToken('installTool', 'upgradeWizardsInput'),
            'upgradeWizardsExecuteToken' => $formProtection->generateToken('installTool', 'upgradeWizardsExecute'),
        ]);
        return $this->view->render();
    }

    /**
     * Get a list of '.rst' files and their details for "Upgrade documentation" view.
     *
     * @return array
     */
    protected function getDocumentationFiles(): array
    {
        $documentationFileService = new DocumentationFile();
        $documentationFiles = $documentationFileService->findDocumentationFiles(
            strtr(realpath(PATH_site . ExtensionManagementUtility::siteRelPath('core') . 'Documentation/Changelog'), '\\', '/')
        );
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

        return [
            'normalFiles' => $documentationFiles,
            'readFiles' => $readFiles,
            'notAffectedFiles' => $notAffectedFiles,
        ];
    }
}
