<?php
namespace TYPO3\CMS\Cshmanual\Service;

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

/**
 * Access service
 */
class AccessService
{
    /**
     * Check if current backend user has access to given identifier
     *
     * @param string $type The type
     * @param string $identifier The search string in access list
     * @return bool TRUE if the user has access
     */
    public function checkAccess($type, $identifier)
    {
        if (!empty($type) && !empty($identifier)) {
            return $this->getBackendUser()->check($type, $identifier);
        }
        return false;
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
