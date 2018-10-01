<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Authentication;

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

use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\SessionService;

/**
 * Authenticates a user (currently comparing it through the install tool password, but could be extended)
 * @internal only to be used within EXT:install
 */
class AuthenticationService
{
    /**
     * @var SessionService
     */
    protected $sessionService;

    /**
     * @param SessionService $sessionService
     */
    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Checks against a given password
     *
     * @param string $password
     * @return bool if authentication was successful, otherwise false
     */
    public function loginWithPassword($password = null): bool
    {
        $validPassword = false;
        if ($password !== null && $password !== '') {
            $installToolPassword = $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'];
            $hashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
            try {
                $hashInstance = $hashFactory->get($installToolPassword, 'BE');
                $validPassword = $hashInstance->checkPassword($password, $installToolPassword);
            } catch (InvalidPasswordHashException $invalidPasswordHashException) {
                // Given hash in global configuration is not a valid salted password
                if (md5($password) === $installToolPassword) {
                    // Update configured install tool hash if it is still "MD5" and password matches
                    // @todo: This should be removed in TYPO3 v10.0 with a dedicated breaking patch
                    // @todo: Additionally, this code should check required hash updates and update the hash if needed
                    $hashInstance = $hashFactory->getDefaultHashInstance('BE');
                    $configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
                    $configurationManager->setLocalConfigurationValueByPath(
                        'BE/installToolPassword',
                        $hashInstance->getHashedPassword($password)
                    );
                    $validPassword = true;
                } else {
                    // Still no valid hash instance could be found. Probably the stored hash used a mechanism
                    // that is not available on current system. We throw the previous exception again to be
                    // handled on a higher level. The install tool will render an according exception message
                    // that links to the wiki.
                    throw $invalidPasswordHashException;
                }
            }
        }
        if ($validPassword) {
            $this->sessionService->setAuthorized();
            $this->sendLoginSuccessfulMail();
            return true;
        }
        $this->sendLoginFailedMail();
        return false;
    }

    /**
     * If install tool login mail is set, send a mail for a successful login.
     */
    protected function sendLoginSuccessfulMail()
    {
        $warningEmailAddress = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
        if ($warningEmailAddress) {
            $mailMessage = GeneralUtility::makeInstance(MailMessage::class);
            $mailMessage
                ->addTo($warningEmailAddress)
                ->setSubject('Install Tool Login at \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\'')
                ->addFrom($this->getSenderEmailAddress(), $this->getSenderEmailName())
                ->setBody('There has been an Install Tool login at TYPO3 site'
                    . ' \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\''
                    . ' (' . GeneralUtility::getIndpEnv('HTTP_HOST') . ')'
                    . ' from remote address \'' . GeneralUtility::getIndpEnv('REMOTE_ADDR') . '\'')
                ->send();
        }
    }

    /**
     * If install tool login mail is set, send a mail for a failed login.
     */
    protected function sendLoginFailedMail()
    {
        $formValues = GeneralUtility::_GP('install');
        $warningEmailAddress = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
        if ($warningEmailAddress) {
            $mailMessage = GeneralUtility::makeInstance(MailMessage::class);
            $mailMessage
                ->addTo($warningEmailAddress)
                ->setSubject('Install Tool Login ATTEMPT at \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\'')
                ->addFrom($this->getSenderEmailAddress(), $this->getSenderEmailName())
                ->setBody('There has been an Install Tool login attempt at TYPO3 site'
                    . ' \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\''
                    . ' (' . GeneralUtility::getIndpEnv('HTTP_HOST') . ')'
                    . ' The last 5 characters of the MD5 hash of the password tried was \'' . substr(md5($formValues['password']), -5) . '\''
                    . ' remote address was \'' . GeneralUtility::getIndpEnv('REMOTE_ADDR') . '\'')
                ->send();
        }
    }

    /**
     * Get sender address from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
     * If this setting is empty fall back to 'no-reply@example.com'
     *
     * @return string Returns an email address
     */
    protected function getSenderEmailAddress()
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
            ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
            : 'no-reply@example.com';
    }

    /**
     * Gets sender name from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
     * If this setting is empty, it falls back to a default string.
     *
     * @return string
     */
    protected function getSenderEmailName()
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'])
            ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
            : 'TYPO3 CMS install tool';
    }
}
