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
use TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedInEvent;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
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
final class EmailLoginNotification implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private int $warningMode = 0;
    private string $warningEmailRecipient = '';

    /**
     * @var ServerRequestInterface
     */
    private $request;

    public function __construct(
        private readonly MailerInterface $mailer
    ) {
        $this->warningMode = (int)($GLOBALS['TYPO3_CONF_VARS']['BE']['warning_mode'] ?? 0);
        $this->warningEmailRecipient = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'] ?? '';
    }

    /**
     * Sends an email notification to warning_email_address and/or the logged-in user's email address.
     */
    public function emailAtLogin(AfterUserLoggedInEvent $event): void
    {
        if (!$event->getUser() instanceof BackendUserAuthentication) {
            return;
        }
        $currentUser = $event->getUser();
        $user = $currentUser->user;
        $genericLoginWarning = $this->warningMode > 0 && !empty($this->warningEmailRecipient);
        $userLoginNotification = ($currentUser->uc['emailMeAtLogin'] ?? null) && GeneralUtility::validEmail($user['email']);
        if (!$genericLoginWarning && !$userLoginNotification) {
            return;
        }
        $this->request = $event->getRequest() ?? $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();

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
            $this->mailer->send($email);
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
