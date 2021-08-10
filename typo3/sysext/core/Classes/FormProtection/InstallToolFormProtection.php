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

/**
 * This class provides protection against cross-site request forgery (XSRF/CSRF)
 * in the install tool.
 *
 *
 * How to use this in the install tool:
 *
 * For each form in the install tool (or link that changes some data), create a
 * token and insert is as a hidden form element. The name of the form element
 * does not matter; you only need it to get the form token for verifying it.
 *
 * <pre>
 * $formToken = $this->formProtection->generateToken(
 * 'installToolPassword', 'change'
 * );
 * then puts the generated form token in a hidden field in the template
 * </pre>
 *
 * The three parameters $formName, $action and $formInstanceName can be
 * arbitrary strings, but they should make the form token as specific as
 * possible. For different forms (e.g. the password change and editing a the
 * configuration), those values should be different.
 *
 * When processing the data that has been submitted by the form, you can check
 * that the form token is valid like this:
 *
 * <pre>
 * if ($dataHasBeenSubmitted && $this->formProtection()->validateToken(
 * $_POST['formToken'],
 * 'installToolPassword',
 * 'change'
 * ) {
 * processes the data
 * } else {
 * no need to do anything here as the install tool form protection will
 * create an error message for an invalid token
 * }
 * </pre>
 */
/**
 * Install Tool form protection
 */
class InstallToolFormProtection extends AbstractFormProtection
{
    /**
     * Retrieves or generates the session token.
     */
    protected function retrieveSessionToken(): string
    {
        if (isset($_SESSION['installToolFormToken']) && !empty($_SESSION['installToolFormToken'])) {
            $this->sessionToken = $_SESSION['installToolFormToken'];
        } else {
            $this->sessionToken = $this->generateSessionToken();
            $this->persistSessionToken();
        }
        return $this->sessionToken;
    }

    /**
     * Saves the tokens so that they can be used by a later incarnation of this
     * class.
     */
    public function persistSessionToken()
    {
        $_SESSION['installToolFormToken'] = $this->sessionToken;
    }
}
