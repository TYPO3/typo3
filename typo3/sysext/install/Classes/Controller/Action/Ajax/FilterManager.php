<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\UpgradeAnalysis\DocumentationFile;

/**
  * Reveal documentation files hidden from views
  */
class FilterManager extends AbstractAjaxAction
{

    /**
     * Executes the action
     *
     * @return string Rendered content
     */
    protected function executeAction(): string
    {
        $registry = new Registry();
        $ignoredFiles = $registry->get('upgradeAnalysisIgnoreFilter', 'ignoredDocumentationFiles', []);
        $documentationFileService = GeneralUtility::makeInstance(DocumentationFile::class);
        $files = [];
        foreach ($ignoredFiles as $filePath) {
            $file = current($documentationFileService->getListEntry($filePath));
            $files[$file['headline']] = $file['filepath'];
        }
        $this->view->assign('files', $files);
        return $this->view->render();
    }
}
