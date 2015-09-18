<?php
namespace TYPO3\CMS\Core\FormProtection;

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
 * This class is a dummy implementation of the form protection,
 * which is used when no authentication is used.
 */
class DisabledFormProtection extends AbstractFormProtection
{
    /**
     * Disable parent method
     *
     * @param string $formName
     * @param string $action
     * @param string $formInstanceName
     * @return string
     */
    public function generateToken($formName, $action = '', $formInstanceName = '')
    {
        return 'dummyToken';
    }

    /**
     * Disable parent method.
     * Always return TRUE.
     *
     * @param string $tokenId
     * @param string $formName
     * @param string $action
     * @param string $formInstanceName
     * @return bool
     */
    public function validateToken($tokenId, $formName, $action = '', $formInstanceName = '')
    {
        return true;
    }

    /**
     * Dummy implementation
     */
    protected function retrieveSessionToken()
    {
    }

    /**
     * Dummy implementation
     */
    public function persistSessionToken()
    {
    }
}
