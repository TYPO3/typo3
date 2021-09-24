<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Security;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Sends out an email if a backend user has just been logged in.
 *
 * Relevant settings:
 * $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_mode']
 * $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr']
 * $BE_USER->uc['emailMeAtLogin']
 *
 * @internal this is not part of TYPO3 API as this is an internal hook
 */
class EmailLoginNotification implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var int
     */
    private $warningMode;

    /**
     * @var string
     */
    private $warningEmailRecipient;

    /**
     * @var ServerRequestInterface
     */
    private $request;

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
        $genericLoginWarning = $this->warningMode > 0 && !empty($this->warningEmailRecipient);
        $userLoginNotification = ($currentUser->uc['emailMeAtLogin'] ?? null) && GeneralUtility::validEmail($user['email']);
        if (!$genericLoginWarning && !$userLoginNotification) {
            return;
        }
        $this->request = $parameters['request'] ?? $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();

        if ($genericLoginWarning) {
            $prefix = $currentUser->isAdmin() ? '[AdminLoginWarning]' : '[LoginWarning]';
            if ($this->warningMode & 1) {
                // First bit: Send warning email on any login
                $this->sendEmail($this->warningEmailRecipient, $currentUser, $prefix);
            } elseif ($currentUser->isAdmin() && $this->warningMode & 2) {
                // Second bit: Only send warning email when an admin logs in
                $this->sendEmail($this->warningEmailRecipient, $currentUser, $prefix);
            }
        }
        // Trigger an email to the current BE user, if this has been enabled in the user configuration
        if ($userLoginNotification) {
            $this->sendEmail($user['email'], $currentUser);
        }
    }

    /**
     * Sends an email.
     *
     * @param string $recipient
     * @param AbstractUserAuthentication $user
     * @param string|null $subjectPrefix
     */
    protected function sendEmail(string $recipient, AbstractUserAuthentication $user, ?string $subjectPrefix = null): void
    {
        $headline = 'TYPO3 Backend Login notification';
        $recipients = explode(',', $recipient);
        $email = GeneralUtility::makeInstance(FluidEmail::class)
            ->to(...$recipients)
            ->setRequest($this->request)
            ->setTemplate('Security/LoginNotification')
            ->assignMultiple([
                'user' => $user->user,
                'prefix' => $subjectPrefix,
                'language' => ($user->user['lang'] ?? '') ?: 'default',
                'headline' => $headline,
            ]);
        try {
            GeneralUtility::makeInstance(Mailer::class)->send($email);
        } catch (TransportException $e) {
            $this->logger->warning('Could not send notification email to "{recipient}" due to mailer settings error', [
                'recipient' => $recipient,
                'userId' => $user->user['uid'] ?? 0,
                'recipientList' => $recipients,
                'exception' => $e,
            ]);
        } catch (RfcComplianceException $e) {
            $this->logger->warning('Could not send notification email to "{recipient}" due to invalid email address', [
                'recipient' => $recipient,
                'userId' => $user->user['uid'] ?? 0,
                'recipientList' => $recipients,
                'exception' => $e,
            ]);
        }
    }
}
