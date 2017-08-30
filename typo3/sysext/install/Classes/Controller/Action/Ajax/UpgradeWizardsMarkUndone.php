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
use TYPO3\CMS\Install\Service\UpgradeWizardsService;

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

        $messages = new FlashMessageQueue('install');
        if ($result) {
            $messages->enqueue(new FlashMessage(
                '',
                'Wizard has been marked undone'
            ));
        } else {
            $messages->enqueue(new FlashMessage(
                '',
                'Wizard has not been marked undone',
                FlashMessage::ERROR
            ));
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages,
        ]);
        return $this->view->render();
    }
}
