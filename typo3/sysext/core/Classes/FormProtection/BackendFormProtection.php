<?php

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

namespace TYPO3\CMS\Core\FormProtection;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Error\Exception;
use TYPO3\CMS\Core\Registry;

/**
 * This class provides protection against cross-site request forgery (XSRF/CSRF)
 * for forms in the BE.
 *
 * How to use:
 *
 * For each form in the BE (or link that changes some data), create a token and
 * insert is as a hidden form element. The name of the form element does not
 * matter; you only need it to get the form token for verifying it.
 *
 * <pre>
 * $formToken = TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()
 * ->generateToken(
 * 'BE user setup', 'edit'
 * );
 * $this->content .= '<input type="hidden" name="formToken" value="' .
 * $formToken . '" />';
 * </pre>
 *
 * The three parameters $formName, $action and $formInstanceName can be
 * arbitrary strings, but they should make the form token as specific as
 * possible. For different forms (e.g. BE user setup and editing a tt_content
 * record) or different records (with different UIDs) from the same table,
 * those values should be different.
 *
 * For editing a tt_content record, the call could look like this:
 *
 * <pre>
 * $formToken = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()
 * ->getFormProtection()->generateToken(
 * 'tt_content', 'edit', $uid
 * );
 * </pre>
 *
 *
 * When processing the data that has been submitted by the form, you can check
 * that the form token is valid like this:
 *
 * <pre>
 * if ($dataHasBeenSubmitted && TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()
 * ->validateToken(
 * \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('formToken'),
 * 'BE user setup', 'edit
 * )
 * ) {
 * processes the data
 * } else {
 * no need to do anything here as the BE form protection will create a
 * flash message for an invalid token
 * }
 * </pre>
 */
class BackendFormProtection extends AbstractFormProtection
{
    /**
     * Keeps the instance of the user which existed during creation
     * of the object.
     *
     * @var BackendUserAuthentication
     */
    protected $backendUser;

    /**
     * Instance of the registry, which is used to permanently persist
     * the session token so that it can be restored during re-login.
     *
     * @var Registry
     */
    protected $registry;

    /**
     * Only allow construction if we have an authorized backend session
     *
     * @param BackendUserAuthentication $backendUser
     * @param Registry $registry
     * @param \Closure $validationFailedCallback
     * @throws \TYPO3\CMS\Core\Error\Exception
     */
    public function __construct(BackendUserAuthentication $backendUser, Registry $registry, \Closure $validationFailedCallback = null)
    {
        $this->backendUser = $backendUser;
        $this->registry = $registry;
        $this->validationFailedCallback = $validationFailedCallback;
        if (!$this->isAuthorizedBackendSession()) {
            throw new Exception('A back-end form protection may only be instantiated if there is an active back-end session.', 1285067843);
        }
    }

    /**
     * Retrieves the saved session token or generates a new one.
     *
     * @return string
     */
    protected function retrieveSessionToken()
    {
        $this->sessionToken = $this->backendUser->getSessionData('formProtectionSessionToken');
        if (empty($this->sessionToken)) {
            $this->sessionToken = $this->generateSessionToken();
            $this->persistSessionToken();
        }
        return $this->sessionToken;
    }

    /**
     * Saves the tokens so that they can be used by a later incarnation of this
     * class.
     *
     * @internal
     */
    public function persistSessionToken()
    {
        $this->backendUser->setAndSaveSessionData('formProtectionSessionToken', $this->sessionToken);
    }

    /**
     * Sets the session token for the user from the registry
     * and returns it additionally.
     *
     * @internal
     * @return string
     * @throws \UnexpectedValueException
     */
    public function setSessionTokenFromRegistry()
    {
        $this->sessionToken = $this->registry->get('core', 'formProtectionSessionToken:' . $this->backendUser->user['uid']);
        if (empty($this->sessionToken)) {
            throw new \UnexpectedValueException('Failed to restore the session token from the registry.', 1301827270);
        }
        return $this->sessionToken;
    }

    /**
     * Stores the session token in the registry to have it
     * available during re-login of the user.
     *
     * @internal
     */
    public function storeSessionTokenInRegistry()
    {
        $this->registry->set('core', 'formProtectionSessionToken:' . $this->backendUser->user['uid'], $this->getSessionToken());
    }

    /**
     * Removes the session token for the user from the registry.
     *
     * @internal
     */
    public function removeSessionTokenFromRegistry()
    {
        $this->registry->remove('core', 'formProtectionSessionToken:' . $this->backendUser->user['uid']);
    }

    /**
     * Checks if a user is logged in and the session is active.
     *
     * @return bool
     */
    protected function isAuthorizedBackendSession()
    {
        return !empty($this->backendUser->user['uid']);
    }
}
