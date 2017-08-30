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
 * Perform "silent" wizard upgrades on first opening of the card
 */
class UpgradeWizardsSilentUpgrades extends AbstractAjaxAction
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

        // Perform silent cache framework table upgrade
        $upgradeWizardsService = new UpgradeWizardsService();
        $statements = $upgradeWizardsService->silentCacheFrameworkTableSchemaMigration();

        $messages = new FlashMessageQueue('install');
        if (!empty($statements)) {
            $messages->enqueue(new FlashMessage(
                '',
                'Created some database cache tables.'
            ));
        }

        $this->view->assignMultiple([
            'success' => true,
            'status' => $messages,
        ]);
        return $this->view->render();
    }
}
