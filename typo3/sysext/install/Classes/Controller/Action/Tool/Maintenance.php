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

use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Install\Controller\Action\AbstractAction;
use TYPO3\CMS\Install\Service\Typo3tempFileService;

/**
 * Handle important actions
 */
class Maintenance extends AbstractAction
{
    /**
     * "Maintenance" main page
     *
     * @return string Rendered content
     */
    protected function executeAction(): string
    {
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $this->view->assignMultiple([
            'clearAllCacheOpcodeCaches' => (new OpcodeCacheService())->getAllActive(),
            'clearTablesClearToken' => $formProtection->generateToken('installTool', 'clearTablesClear'),
            'clearTypo3tempFilesStats' => (new Typo3tempFileService())->getDirectoryStatistics(),
            'clearTypo3tempFilesToken' => $formProtection->generateToken('installTool', 'clearTypo3tempFiles'),
            'createAdminToken' => $formProtection->generateToken('installTool', 'createAdmin'),
            'databaseAnalyzerExecuteToken' => $formProtection->generateToken('installTool', 'databaseAnalyzerExecute'),
        ]);
        return $this->view->render();
    }
}
