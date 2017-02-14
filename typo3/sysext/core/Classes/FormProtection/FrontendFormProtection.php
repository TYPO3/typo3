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

use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * This class provides protection against cross-site request forgery (XSRF/CSRF)
 * for actions in the frontend that change data.
 *
 * How to use:
 *
 * For each form (or link that changes some data), create a token and
 * insert is as a hidden form element or use it as GET argument. The name of the form element does not
 * matter; you only need it to get the form token for verifying it.
 *
 * <pre>
 * $formToken = TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()
 * ->generateToken(
 * 'User setup', 'edit'
 * );
 * $this->content .= '<input type="hidden" name="formToken" value="' .
 * $formToken . '" />';
 * </pre>
 *
 * The three parameters $formName, $action and $formInstanceName can be
 * arbitrary strings, but they should make the form token as specific as
 * possible. For different forms (e.g. User setup and editing a news
 * record) or different records (with different UIDs) from the same table,
 * those values should be different.
 *
 * For editing a news record, the call could look like this:
 *
 * <pre>
 * $formToken = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()
 * ->getFormProtection()->generateToken(
 * 'news', 'edit', $uid
 * );
 * </pre>
 *
 *
 * When processing the data that has been submitted by the form, you can check
 * that the form token is valid like this:
 *
 * <pre>
 * if ($dataHasBeenSubmitted && \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()
 * ->validateToken(
 * \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('formToken'),
 * 'User setup', 'edit
 * )
 * ) {
 * Processes the data.
 * } else {
 * Create a flash message for the invalid token or just discard this request.
 * }
 * </pre>
 */
class FrontendFormProtection extends AbstractFormProtection
{
    /**
     * Keeps the instance of the user which existed during creation
     * of the object.
     *
     * @var FrontendUserAuthentication
     */
    protected $frontendUser;

    /**
     * Only allow construction if we have an authorized frontend session
     *
     * @param FrontendUserAuthentication $frontendUser
     * @param \Closure $validationFailedCallback
     * @throws \TYPO3\CMS\Core\Error\Exception
     */
    public function __construct(FrontendUserAuthentication $frontendUser, \Closure $validationFailedCallback = null)
    {
        $this->frontendUser = $frontendUser;
        $this->validationFailedCallback = $validationFailedCallback;
        if (!$this->isAuthorizedFrontendSession()) {
            throw new \TYPO3\CMS\Core\Error\Exception('A front-end form protection may only be instantiated if there is an active front-end session.', 1460975777);
        }
    }

    /**
     * Retrieves the saved session token or generates a new one.
     *
     * @return string
     */
    protected function retrieveSessionToken()
    {
        $this->sessionToken = $this->frontendUser->getSessionData('formProtectionSessionToken');
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
     * @access private
     */
    public function persistSessionToken()
    {
        $this->frontendUser->setAndSaveSessionData('formProtectionSessionToken', $this->sessionToken);
    }

    /**
     * Checks if a user is logged in and the session is active.
     *
     * @return bool
     */
    protected function isAuthorizedFrontendSession()
    {
        return !empty($this->frontendUser->user['uid']);
    }
}
