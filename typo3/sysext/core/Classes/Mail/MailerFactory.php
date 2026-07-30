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

namespace TYPO3\CMS\Core\Mail;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Creates mailers for named mail profiles.
 *
 * Inject this factory instead of the MailerInterface when an email must not be sent through
 * the globally configured transport, for example when newsletters and transactional emails
 * are delivered by different providers.
 *
 * Mailers are kept per profile, so an established SMTP connection is reused for all emails
 * sent through the same profile within one request.
 */
#[Autoconfigure(public: true)]
class MailerFactory
{
    /**
     * @var array<string, MailerInterface>
     */
    private array $mailers = [];

    public function __construct(
        private readonly MailProfileResolver $mailProfileResolver,
        private readonly TransportFactory $transportFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly MailerInterface $mailer,
    ) {}

    /**
     * Returns the mailer of the given profile, or the globally configured mailer without one.
     *
     * @throws \InvalidArgumentException if the given profile is not configured
     */
    public function getMailer(?string $profileName = null): MailerInterface
    {
        if ($profileName === null || $profileName === '') {
            return $this->mailer;
        }
        return $this->mailers[$profileName] ??= $this->createMailer($profileName);
    }

    private function createMailer(string $profileName): MailerInterface
    {
        $mailSettings = $this->mailProfileResolver->getSettings($profileName);
        return new Mailer(
            $this->transportFactory->get($mailSettings),
            $this->eventDispatcher,
            $mailSettings
        );
    }
}
