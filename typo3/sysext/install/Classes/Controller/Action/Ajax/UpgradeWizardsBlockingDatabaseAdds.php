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
 * "Blocking" upgrade to add missing fields + tables to DB.
 * This one must be green before others can be executed
 */
class UpgradeWizardsBlockingDatabaseAdds extends AbstractAjaxAction
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
        $adds = $upgradeWizardsService->getBlockingDatabaseAdds();

        $needsUpdate = false;
        if (!empty($adds)) {
            $needsUpdate = true;
        }

        $this->view->assignMultiple([
            'success' => true,
            'needsUpdate' => $needsUpdate,
            'adds' => $adds,
        ]);
        return $this->view->render();
    }
}
