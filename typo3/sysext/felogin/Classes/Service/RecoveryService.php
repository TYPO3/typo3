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

namespace TYPO3\CMS\FrontendLogin\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\FrontendLogin\Configuration\RecoveryConfiguration;
use TYPO3\CMS\FrontendLogin\Event\SendRecoveryEmailEvent;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class RecoveryService
{
    protected array $settings;

    public function __construct(
        protected readonly MailerInterface $mailer,
        protected EventDispatcherInterface $eventDispatcher,
        ConfigurationManagerInterface $configurationManager,
        protected RecoveryConfiguration $recoveryConfiguration,
        protected UriBuilder $uriBuilder
    ) {
        $this->settings = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);
    }

    /**
     * Sends an email with an absolute link including the given forgot hash to the passed user
     * with instructions to recover the account.
     *
     * @throws TransportExceptionInterface
     */
    public function sendRecoveryEmail(RequestInterface $request, array $userData, string $hash): void
    {
        $this->uriBuilder->setRequest($request);

        $receiver = new Address($userData['email'], $this->getReceiverName($userData));
        $email = $this->prepareMail($request, $receiver, $hash);

        $event = new SendRecoveryEmailEvent($email, $userData);
        $this->eventDispatcher->dispatch($event);
        $this->mailer->send($event->getEmail());
    }

    /**
     * Get display name from values. Fallback to username if none of the "_name" fields is set.
     */
    protected function getReceiverName(array $userInformation): string
    {
        $displayName = trim(
            sprintf(
                '%s%s%s',
                $userInformation['first_name'],
                $userInformation['middle_name'] ? " {$userInformation['middle_name']}" : '',
                $userInformation['last_name'] ? " {$userInformation['last_name']}" : ''
            )
        );

        return $displayName ? $displayName . ' (' . $userInformation['username'] . ')' : $userInformation['username'];
    }

    /**
     * Create email object from configuration.
     */
    protected function prepareMail(RequestInterface $request, Address $receiver, string $hash): FluidEmail
    {
        $url = $this->uriBuilder->setCreateAbsoluteUri(true)
            ->uriFor(
                'showChangePassword',
                ['hash' => $hash],
                'PasswordRecovery',
                'felogin',
                'Login'
            );

        $variables = [
            'receiverName' => $receiver->getName(),
            'url' => $url,
            'validUntil' => date($this->settings['dateFormat'], $this->recoveryConfiguration->getLifeTimeTimestamp()),
        ];

        $mailTemplatePaths = $this->recoveryConfiguration->getMailTemplatePaths();

        $mail = GeneralUtility::makeInstance(FluidEmail::class, $mailTemplatePaths);
        $mail->setRequest($request);
        $mail->subject($this->getEmailSubject())
            ->from($this->recoveryConfiguration->getSender())
            ->to($receiver)
            ->assignMultiple($variables)
            ->setTemplate($this->recoveryConfiguration->getMailTemplateName());

        $replyTo = $this->recoveryConfiguration->getReplyTo();
        if ($replyTo) {
            $mail->addReplyTo($replyTo);
        }

        return $mail;
    }

    protected function getEmailSubject(): string
    {
        return LocalizationUtility::translate('password_recovery_mail_header', 'felogin');
    }
}
