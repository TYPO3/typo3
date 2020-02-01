<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Dashboard\Controller;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;

class AbstractController
{
    protected const MODULE_DATA_CURRENT_DASHBOARD_IDENTIFIER = 'web_dashboard/current_dashboard/';

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function loadCurrentDashboard(): string
    {
        return $this->getBackendUser()->getModuleData(self::MODULE_DATA_CURRENT_DASHBOARD_IDENTIFIER) ?? '';
    }

    protected function saveCurrentDashboard(string $identifier): void
    {
        $this->getBackendUser()->pushModuleData(self::MODULE_DATA_CURRENT_DASHBOARD_IDENTIFIER, $identifier);
    }
}
