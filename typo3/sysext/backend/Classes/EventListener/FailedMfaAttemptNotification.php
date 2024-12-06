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

namespace TYPO3\CMS\Backend\EventListener;

use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\Event\MfaVerificationFailedEvent;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Sends out an email to the backend user for failed MFA attempts on TYPO3 backend login
 *
 * @internal this class is not part of the TYPO3 Core API as this is a concrete event listener implementation
 */
final class FailedMfaAttemptNotification
{
    public function __construct(protected readonly MailerInterface $mailer) {}

    /**
     * Sends a notification email to the backend user on failed MFA verification.
     */
    #[AsEventListener(identifier: 'typo3/cms-backend/failed-mfa-attempt-notification')]
    public function __invoke(MfaVerificationFailedEvent $event): void
    {
        if (!$event->isBackendAttempt()) {
            // This notification only works for backend users
            return;
        }

        $backendUser = $event->getUser();
        $emailAddress = $backendUser->user['email'];
        if (!GeneralUtility::validEmail($emailAddress)) {
            return;
        }

        $emailObject = GeneralUtility::makeInstance(FluidEmail::class);
        $emailObject
            ->to(new Address($emailAddress, $backendUser->user['realName']))
            ->setRequest($event->getRequest())
            ->assign('provider', $event->getProvider())
            ->setTemplate('Mfa/FailedMfaNotification');
        $this->mailer->send($emailObject);
    }
}
