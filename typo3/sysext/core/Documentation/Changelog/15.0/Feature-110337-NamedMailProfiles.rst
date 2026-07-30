..  include:: /Includes.rst.txt

..  _feature-110337-1785920446:

======================================
Feature: #110337 - Named mail profiles
======================================

See :issue:`110337`

Description
===========

An installation can now send emails through more than one mail provider. Named mail
profiles are configured in :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles']` and
use the same keys as the global :php:`MAIL` configuration. Each profile is merged on
top of the global settings, so a profile only has to define the keys that differ.

The new class :php:`\TYPO3\CMS\Core\Mail\MailerFactory` creates a mailer for a given
profile. Injecting the factory instead of :php:`\TYPO3\CMS\Core\Mail\MailerInterface`
allows to pick the transport per email, for example to deliver newsletters through a
bulk provider while transactional emails keep using the regular SMTP server.

Mailers are kept per profile, so an established SMTP connection is reused for all
emails sent through the same profile within one request.

Impact
======

Configure the profiles in :file:`config/system/settings.php`:

..  code-block:: php
    :caption: config/system/settings.php (excerpt)

    'MAIL' => [
        'transport' => 'smtp',
        'transport_smtp_server' => 'smtp.example.com:587',
        'defaultMailFromAddress' => 'noreply@example.com',
        'defaultMailFromName' => 'Example',
        'profiles' => [
            'newsletter' => [
                'transport_smtp_server' => 'smtp.bulk-provider.example:587',
                'transport_smtp_username' => 'newsletter',
                'transport_smtp_password' => '***',
                'defaultMailFromAddress' => 'news@example.com',
            ],
            'local' => [
                'transport' => 'sendmail',
                'transport_sendmail_command' => '/usr/sbin/sendmail -bs',
            ],
        ],
    ],

Request the mailer of a profile through the factory:

..  code-block:: php

    use TYPO3\CMS\Core\Mail\FluidEmail;
    use TYPO3\CMS\Core\Mail\MailerFactory;

    final readonly class NewsletterService
    {
        public function __construct(private MailerFactory $mailerFactory) {}

        public function send(FluidEmail $email): void
        {
            $this->mailerFactory->getMailer('newsletter')->send($email);
        }
    }

Calling :php:`getMailer()` without a profile name returns the mailer of the global
:php:`MAIL` configuration, the same instance that is injected as
:php:`\TYPO3\CMS\Core\Mail\MailerInterface`. Requesting an unknown
profile throws an :php:`\InvalidArgumentException`, so that a typo does not silently
deliver emails through the wrong provider.

Besides the transport, a profile also provides the sender defaults
:php:`defaultMailFromAddress`, :php:`defaultMailFromName`,
:php:`defaultMailReplyToAddress` and :php:`defaultMailReplyToName`. They are applied
to emails that do not set a sender or reply-to address themselves. Many providers
reject emails whose :mailheader:`From` header does not belong to a verified domain,
so setting the address explicitly per profile is recommended.

The sender defaults are now read from the settings the mailer was created with
rather than from :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']` at the time an email is
sent. This also applies to the global mailer: code that changes
:php:`defaultMailFromAddress` or the other sender defaults at runtime, for example
in a middleware, has to do so before the mailer is instantiated for the first time.

The new value object :php:`\TYPO3\CMS\Core\Mail\SenderDefaults` resolves these
addresses from the global :php:`MAIL` configuration or the settings of a profile,
including the fallback to a no-reply address when no valid sender is configured:

..  code-block:: php

    use TYPO3\CMS\Core\Mail\MailProfileResolver;
    use TYPO3\CMS\Core\Mail\SenderDefaults;

    $senderDefaults = SenderDefaults::fromMailSettings(
        $mailProfileResolver->getSettings('newsletter')
    );
    $email->from($senderDefaults->from);

Credentials of a profile, such as :php:`transport_smtp_password` or :php:`dsn`, are
masked in the :guilabel:`System > Configuration` module just like those of the
global :php:`MAIL` configuration.

A profile that only defines :php:`dsn` uses the :php:`dsn` transport, so the DSN is
not ignored in favour of the global :php:`transport` setting.

A profile that defines its own :php:`transport`, :php:`transport_smtp_server` or
:php:`dsn` does not inherit :php:`transport_smtp_username`,
:php:`transport_smtp_password` and :php:`dsn` from the global configuration, so the
credentials of the global mail server are never sent to another server. Such a
profile has to define its own credentials if its server requires authentication.

Spooling is configured per profile. Unlike all other keys, the spool settings
:php:`transport_spool_type` and :php:`transport_spool_filepath` are not inherited
from the global configuration, because spooled emails are sent through the transport
of the configuration they were spooled with. A profile without a spool of its own
therefore sends its emails immediately. A file spool of a profile needs a directory
of its own; a profile spooling into the directory of the global configuration or of
another profile is rejected with an :php:`\InvalidArgumentException`.

Since the file spool of a profile has to be flushed with the transport of that very
profile, :bash:`mailer:spool:send` accepts the new option :bash:`--profile`:

..  code-block:: bash

    vendor/bin/typo3 mailer:spool:send --profile=newsletter

Without the option, the command flushes the spool of the global configuration as
before. A memory spool of a profile is flushed through the transport of that profile
at the end of the request.

The constructor of :php:`\TYPO3\CMS\Core\Command\SendEmailCommand` now receives the
:php-short:`\TYPO3\CMS\Core\Mail\MailerFactory` instead of the
:php-short:`\TYPO3\CMS\Core\Mail\MailerInterface`.

..  index:: LocalConfiguration, PHP-API, CLI, ext:core
