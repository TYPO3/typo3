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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides protection against cross-site request forgery (XSRF/CSRF)
 * for forms.
 *
 * For documentation on how to use this class, please see the documentation of
 * the corresponding subclasses
 */
abstract class AbstractFormProtection
{
    /**
     * @var \Closure
     */
    protected $validationFailedCallback;

    /**
     * The session token which is used to be hashed during token generation.
     *
     * @var string
     */
    protected $sessionToken;

    /**
     * @return string
     */
    protected function getSessionToken()
    {
        if ($this->sessionToken === null) {
            $this->sessionToken = $this->retrieveSessionToken();
        }
        return $this->sessionToken;
    }

    /**
     * Frees as much memory as possible.
     */
    public function __destruct()
    {
        unset($this->sessionToken);
    }

    /**
     * Deletes the session token and persists the (empty) token.
     *
     * This function is intended to be called when a user logs on or off.
     *
     * @return void
     */
    public function clean()
    {
        unset($this->sessionToken);
        $this->persistSessionToken();
    }

    /**
     * Generates a token for a form by hashing the given parameters
     * with the secret session token.
     *
     * Calling this function two times with the same parameters will create
     * the same valid token during one user session.
     *
     * @param string $formName
     * @param string $action
     * @param string $formInstanceName
     * @return string the 32-character hex ID of the generated token
     * @throws \InvalidArgumentException
     */
    public function generateToken($formName, $action = '', $formInstanceName = '')
    {
        if ($formName == '') {
            throw new \InvalidArgumentException('$formName must not be empty.', 1294586643);
        }
        $tokenId = GeneralUtility::hmac($formName . $action . $formInstanceName . $this->getSessionToken());
        return $tokenId;
    }

    /**
     * Checks whether the token $tokenId is valid in the form $formName with
     * $formInstanceName.
     *
     * @param string $tokenId
     * @param string $formName
     * @param string $action
     * @param string $formInstanceName
     * @return bool
     */
    public function validateToken($tokenId, $formName, $action = '', $formInstanceName = '')
    {
        $validTokenId = GeneralUtility::hmac(((string)$formName . (string)$action) . (string)$formInstanceName . $this->getSessionToken());
        if ((string)$tokenId === $validTokenId) {
            $isValid = true;
        } else {
            $isValid = false;
        }
        if (!$isValid) {
            $this->createValidationErrorMessage();
        }
        return $isValid;
    }

    /**
     * Generates the random token which is used in the hash for the form tokens.
     *
     * @return string
     */
    protected function generateSessionToken()
    {
        return bin2hex(GeneralUtility::generateRandomBytes(32));
    }

    /**
     * Creates or displays an error message telling the user that the submitted
     * form token is invalid.
     *
     * @return void
     */
    protected function createValidationErrorMessage()
    {
        if ($this->validationFailedCallback !== null) {
            $this->validationFailedCallback->__invoke();
        }
    }

    /**
     * Retrieves the session token.
     *
     * @return string
     */
    abstract protected function retrieveSessionToken();

    /**
     * Saves the session token so that it can be used by a later incarnation
     * of this class.
     *
     * @access private
     * @return void
     */
    abstract public function persistSessionToken();
}
