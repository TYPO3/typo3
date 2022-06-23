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
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\FrontendLogin\Configuration\IncompleteConfigurationException;
use TYPO3\CMS\FrontendLogin\Configuration\RecoveryConfiguration;
use TYPO3\CMS\FrontendLogin\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\FrontendLogin\Event\SendRecoveryEmailEvent;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class RecoveryService implements RecoveryServiceInterface
{
    /**
     * @var array
     */
    protected $settings;

    public function __construct(
        protected Mailer $mailer,
        protected EventDispatcherInterface $eventDispatcher,
        ConfigurationManager $configurationManager,
        protected RecoveryConfiguration $recoveryConfiguration,
        protected UriBuilder $uriBuilder,
        protected FrontendUserRepository $userRepository
    ) {
        $this->settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
    }

    /**
     * Sends an email with an absolute link including a forgot hash to the passed email address
     * with instructions to recover the account.
     *
     * @param string $emailAddress Receiver's email address.
     *
     * @throws TransportExceptionInterface
     * @throws IncompleteConfigurationException
     */
    public function sendRecoveryEmail(string $emailAddress): void
    {
        $hash = $this->recoveryConfiguration->getForgotHash();
        // @todo: This repository method call should be moved to PasswordRecoveryController, since its
        // @todo: unexpected that it happens here. Would also drop the dependency to FrontendUserRepository
        // @todo: in this sendRecoveryEmail() method and the class.
        $this->userRepository->updateForgotHashForUserByEmail($emailAddress, GeneralUtility::hmac($hash));
        $userInformation = $this->userRepository->fetchUserInformationByEmail($emailAddress);
        $receiver = new Address($emailAddress, $this->getReceiverName($userInformation));
        $email = $this->prepareMail($receiver, $hash);

        $event = new SendRecoveryEmailEvent($email, $userInformation);
        $this->eventDispatcher->dispatch($event);
        $this->mailer->send($event->getEmail());
    }

    /**
     * Get display name from values. Fallback to username if none of the "_name" fields is set.
     *
     * @param array $userInformation
     *
     * @return string
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
     *
     * @param Address $receiver
     * @param string $hash
     *
     * @return Email
     * @throws IncompleteConfigurationException
     */
    protected function prepareMail(Address $receiver, string $hash): Email
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
        $mail->subject($this->getEmailSubject())
            ->from($this->recoveryConfiguration->getSender())
            ->to($receiver)
            ->assignMultiple($variables)
            ->setTemplate($this->recoveryConfiguration->getMailTemplateName());

        $replyTo = $this->recoveryConfiguration->getReplyTo();
        if ($replyTo) {
            $mail->addReplyTo($replyTo);
        }

        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            $mail->setRequest($GLOBALS['TYPO3_REQUEST']);
        }

        return $mail;
    }

    protected function getEmailSubject(): string
    {
        return LocalizationUtility::translate('password_recovery_mail_header', 'felogin');
    }
}
