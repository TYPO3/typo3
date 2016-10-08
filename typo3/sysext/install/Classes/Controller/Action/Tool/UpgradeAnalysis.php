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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
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
     */
    protected function executeAction()
    {
        $documentationFileService = new DocumentationFile();
        $documentationFiles = $documentationFileService->findDocumentationFiles(
            PATH_site . ExtensionManagementUtility::siteRelPath('core') . 'Documentation/Changelog'
        );

        $this->view->assign('files', $documentationFiles);
        return $this->view->render();
    }
}
