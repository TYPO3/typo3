<?php
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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Uninstall Extensions
 *
 * Used for uninstalling an extension (or multiple) via an ajax request.
 *
 * If you use this class you have to take care of clearing the cache afterwards,
 * it's not done here because for fully clearing the cache you need a reload
 * to take care of changed cache configurations due to no longer installed extensions.
 * Use the clearCache ajax action afterwards.
 */
class UninstallExtension extends AbstractAjaxAction
{
    /**
     * Uninstall one or multiple extensions
     * Extension keys are read from get vars, more than one extension has to be comma separated
     *
     * @return string "OK" on success, the error message otherwise
     */
    protected function executeAction()
    {
        $getVars = GeneralUtility::_GET('install');
        if (isset($getVars['uninstallExtension']) && isset($getVars['uninstallExtension']['extensions'])) {
            $extensionsToUninstall = GeneralUtility::trimExplode(',', $getVars['uninstallExtension']['extensions']);
            foreach ($extensionsToUninstall as $extension) {
                if (ExtensionManagementUtility::isLoaded($extension)) {
                    try {
                        ExtensionManagementUtility::unloadExtension($extension);
                    } catch (\Exception $e) {
                        return $e->getMessage();
                    }
                }
            }
        }
        return 'OK';
    }
}
