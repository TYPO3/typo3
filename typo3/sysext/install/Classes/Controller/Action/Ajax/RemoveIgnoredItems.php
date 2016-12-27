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
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Install\UpgradeAnalysis\DocumentationFile;

/**
 * Remove ignored items from registry and therefor bring them back
 */
class RemoveIgnoredItems extends AbstractAjaxAction
{

    /**
     * Executes the action
     *
     * @return string Rendered content
     */
    protected function executeAction(): string
    {
        $registry = new Registry();
        $file = $this->postValues['ignoreFile'];

        $ignoredFiles = $registry->get('upgradeAnalysisIgnoreFilter', 'ignoredDocumentationFiles', []);
        $key = array_search($file, $ignoredFiles);
        unset($ignoredFiles[$key]);

        $registry->set('upgradeAnalysisIgnoreFilter', 'ignoredDocumentationFiles', $ignoredFiles);

        $documentationFileService = new DocumentationFile();
        $fileInformation = $documentationFileService->getListEntry($file);
        $issueNumber = key($fileInformation);
        $this->view->assign('fileArray', current($fileInformation));
        $this->view->assign('issueNumber', $issueNumber);

        return $this->view->render();
    }
}
