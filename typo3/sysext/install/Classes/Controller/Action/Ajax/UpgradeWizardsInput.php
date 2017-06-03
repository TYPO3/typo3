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

/**
 * Get user input of a specific upgrade wizard
 */
class UpgradeWizardsInput extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        // ext_localconf, db and ext_tables must be loaded for the updates :(
        $this->loadExtLocalconfDatabaseAndExtTables();

        $upgradeWizardsService = new UpgradeWizardsService();

        $identifier = $this->postValues['identifier'];
        $result = $upgradeWizardsService->getWizardUserInput($identifier);

        $messages = [];
        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages,
            'userInput' => $result,
        ]);
        return $this->view->render();
    }
}
