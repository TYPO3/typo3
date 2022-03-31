.. include:: /Includes.rst.txt

=============================================================
Breaking: #88643 - Removed swiftmailer/swiftmailer dependency
=============================================================

See :issue:`88643`

Description
===========

TYPO3's dependency swiftmailer has been removed in favor of new symfony-based
components "mime" and "mailer".

This means that all SwiftMailer-related PHP code has been removed.


Impact
======

Custom SwiftMailer plugins or transports cannot be used without further
migration anymore and will result in a fatal :php:`E_ERROR`.

Using SwiftMailer-specific API by using TYPO3's :php:`TYPO3\CMS\Core\Mail\MailMessage` class might result
in fatal :php:`E_ERROR` when sending out emails.


Affected Installations
======================

Any TYPO3 installation with third-party extension sending out emails or extending
TYPO3's email sending capabilities.


Migration
=========

Search the third-party extensions' code for occurrences of MailMessage or
parts starting with `\Swift_` and migrate to symfony/mime or symfony/mailer
APIs, which are included in TYPO3 v10.0.

If required, SwiftMailer code can be installed via composer (when running TYPO3 via composer)
via `composer require swiftmailer/swiftmailer`.

.. index:: PHP-API, NotScanned, ext:core
