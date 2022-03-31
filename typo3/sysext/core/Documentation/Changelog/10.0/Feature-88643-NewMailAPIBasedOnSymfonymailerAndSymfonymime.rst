.. include:: /Includes.rst.txt

=======================================================================
Feature: #88643 - New Mail API based on symfony/mailer and symfony/mime
=======================================================================

See :issue:`88643`

Description
===========

TYPO3 has relied on the third-party dependency "SwiftMailer" for a long time.

However, the library has been superseded by the author in favor of new, more modern
libraries "symfony/mailer" for sending emails and "symfony/mime" for creating email
messages.

TYPO3 has replaced swiftmailer with the symfony components.

The new component does not handle the regular PHP function :php:`mail()`, which
has been declared unsafe in various scenarios, anymore. Instead it is recommended
to switch to `sendmail` or `smtp`, which can be configured within the TYPO3
Install Tool or the Settings module for System Maintainers under "Presets" => "Mail".

All existing installations which still configure ``mail`` are migrated to ``sendmail``
by automatically detecting the sendmail path by checking PHP.ini settings, but
should be reviewed on update.

In addition, the MailMessage API to create Email messages now inherits from
:php:`Symfony\Mail\Email` instead of :php:`Swift_Message`, and adds certain shortcuts
and more flexibility, but is also stricter in validation.

Especially custom extensions using the MailMessage API need to be evaluated,
as it is not possible anymore to add multiple email addresses as a simple associative
array but rather an Address object from "symfony/mime" is required.

All existing Swiftmailer-based transports which TYPO3 supports natively have been
replaced by Symfony-based transport APIs.

Spool-based transports are still experimental, as it might be replaced by a native
Symfony component as well.


Impact
======

The MailMessage API now has more possibilities to add multi-part files and attachments,
for use in third-party extensions, but some APIs might be adapted.

See the documentation of the Symfony components (https://symfony.com/doc/current/mailer.html)
for further details on how to use the new Email class where TYPO3's MailMessage
class extends from.

An example implementation within a third-party extension:

.. code-block:: php

    $email = GeneralUtility::makeInstance(MailMessage::class)
         ->to(new Address('kasperYYYY@typo3.org'), new Address('benni@typo3.org', 'Benni Mack'))
         ->subject('This is an example email')
         ->text('This is the plain-text variant')
         ->html('<h4>Hello Benni.</h4><p>Enjoy a HTML-readable email. <marquee>We love TYPO3</marquee>.</p>');

    $email->send();

It is however also possible to re-use a Mailer instance, also adding custom Mailer
settings via a custom Transport for special cases.

.. code-block:: php

    $mailer = GeneralUtility::makeInstance(Mailer::class)

    $email = GeneralUtility::makeInstance(MailMessage::class)
         ->to(new Address('kasperYYYY@typo3.org'), new Address('benni@typo3.org', 'Benni Mack'))
         ->subject('This is an example email')
         ->text('This is the plain-text variant')
         ->html('<h4>Hello Benni.</h4><p>Enjoy a HTML-readable email. <marquee>We love TYPO3</marquee>.</p>');

    // Send the email via the Mailer instance
    $mailer->send($email);

.. index:: PHP-API, ext:core
