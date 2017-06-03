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

use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use TYPO3\CMS\Install\Status\ErrorStatus;
use TYPO3\CMS\Install\Status\OkStatus;

/**
 * Mark a wizard as undone in registry. Can be either a
 * casual wizard, or a "row updater" wizard.
 */
class UpgradeWizardsMarkUndone extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $this->loadExtLocalconfDatabaseAndExtTables();

        $wizardToBeMarkedAsUndoneIdentifier = $this->postValues['identifier'];
        $upgradeWizardsService = new UpgradeWizardsService();
        $result = $upgradeWizardsService->markWizardUndoneInRegistry($wizardToBeMarkedAsUndoneIdentifier);

        $messages = [];
        if ($result) {
            $message = new OkStatus();
            $message->setTitle('Wizard has been marked undone');
            $messages[] = $message;
        } else {
            $message = new ErrorStatus();
            $message->setTitle('Wizard has not been marked undone');
            $messages[] = $message;
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages,
        ]);
        return $this->view->render();
    }
}
