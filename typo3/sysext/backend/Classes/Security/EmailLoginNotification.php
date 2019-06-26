<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\Security;

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

use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Sends out an email if a backend user has just been logged in.
 *
 * Interesting settings:
 * $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_mode']
 * $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr']
 * $BE_USER->uc['emailMeAtLogin']
 *
 * @internal this is not part of TYPO3 API as this is an internal hook
 */
class EmailLoginNotification
{
    /**
     * @var int
     */
    private $warningMode;

    /**
     * @var string
     */
    private $warningEmailRecipient;

    public function __construct()
    {
        $this->warningMode = (int)($GLOBALS['TYPO3_CONF_VARS']['BE']['warning_mode'] ?? 0);
        $this->warningEmailRecipient = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'] ?? '';
    }

    /**
     * Sends an email notification to warning_email_address and/or the logged-in user's email address.
     *
     * @param array $parameters array data
     * @param BackendUserAuthentication $currentUser the currently just-logged in user
     */
    public function emailAtLogin(array $parameters, BackendUserAuthentication $currentUser): void
    {
        $user = $parameters['user'];

        $subject = 'At "' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '" from ' . GeneralUtility::getIndpEnv('REMOTE_ADDR');
        $emailBody = $this->compileEmailBody(
            $user,
            GeneralUtility::getIndpEnv('REMOTE_ADDR'),
            GeneralUtility::getIndpEnv('HTTP_HOST'),
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']
        );

        if ($this->warningMode > 0 && !empty($this->warningEmailRecipient)) {
            $prefix = $currentUser->isAdmin() ? '[AdminLoginWarning]' : '[LoginWarning]';
            if ($this->warningMode & 1) {
                // First bit: Send warning email on any login
                $this->sendEmail($this->warningEmailRecipient, $prefix . ' ' . $subject, $emailBody);
            } elseif ($currentUser->isAdmin() && $this->warningMode & 2) {
                // Second bit: Only send warning email when an admin logs in
                $this->sendEmail($this->warningEmailRecipient, $prefix . ' ' . $subject, $emailBody);
            }
        }
        // Trigger an email to the current BE user, if this has been enabled in the user configuration
        if ($currentUser->uc['emailMeAtLogin'] && GeneralUtility::validEmail($user['email'])) {
            $this->sendEmail($user['email'], $subject, $emailBody);
        }
    }

    /**
     * Sends an email.
     *
     * @param string $recipient
     * @param string $subject
     * @param string $body
     */
    protected function sendEmail(string $recipient, string $subject, string $body): void
    {
        $recipients = explode(',', $recipient);
        GeneralUtility::makeInstance(MailMessage::class)
            ->to(...$recipients)
            ->subject($subject)
            ->text($body)
            ->send();
    }

    protected function compileEmailBody(array $user, string $ipAddress, string $httpHost, string $siteName): string
    {
        return sprintf(
            'User "%s" logged in from %s at "%s" (%s)',
            $user['username'],
            $ipAddress,
            $siteName,
            $httpHost
        );
    }
}
