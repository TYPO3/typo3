<?php
namespace TYPO3\CMS\Impexp\Hook;

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

/**
 * Hook for page tree context menu to suppress "import .t3d" menu item
 * if user is no admin and options.impexp.enableImportForNonAdminUser is
 * not set in userTsConfig
 */
class ContextMenuDisableItemsHook
{
    /**
     * Remove import functionality from page tree context menu
     * if user is no admin and this module is not enabled via userTsConfig
     *
     * Modifies $parameters array by reference!
     *
     * @param array $parameters Parameter array
     */
    public function disableImportForNonAdmin(array $parameters)
    {
        $backendUser = $this->getBackendUser();
        if (!$backendUser->isAdmin()) {
            $isEnabledForNonAdmin = $backendUser->getTSConfig('options.impexp.enableImportForNonAdminUser');
            if (empty($isEnabledForNonAdmin['value'])) {
                $parameters['disableItems'][] = 'importT3d';
            }
        }
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
