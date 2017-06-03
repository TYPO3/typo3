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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\Status\StatusUtility;

/**
 * Get folder structure status
 */
class FolderStructureGetStatus extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $structureFacade = $folderStructureFactory->getStructure();

        $statusObjects = $structureFacade->getStatus();
        $statusUtility = GeneralUtility::makeInstance(StatusUtility::class);

        $errorStatus = array_merge(
            $statusUtility->filterBySeverity($statusObjects, 'error'),
            $statusUtility->filterBySeverity($statusObjects, 'warning')
        );
        $okStatus = array_merge(
            $statusUtility->filterBySeverity($statusObjects, 'notice'),
            $statusUtility->filterBySeverity($statusObjects, 'information'),
            $statusUtility->filterBySeverity($statusObjects, 'ok')
        );

        $this->view->assignMultiple([
            'success' => true,
            'errorStatus' => $errorStatus,
            'okStatus' => $okStatus,
        ]);
        return $this->view->render();
    }
}
