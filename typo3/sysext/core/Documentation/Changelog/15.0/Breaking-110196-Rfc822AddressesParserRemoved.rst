.. include:: /Includes.rst.txt

.. _breaking-110196-1784119109:

===========================================================
Breaking: #110196 - PHP class Rfc822AddressesParser removed
===========================================================

See :issue:`110196`

Description
===========

The PHP class :php:`\TYPO3\CMS\Core\Mail\Rfc822AddressesParser` has been
removed. The class was a modernized copy of the PEAR package
:composer:`pear/mail`, dating back to 2010, and was only used internally by
:php:`\TYPO3\CMS\Core\Utility\MailUtility::parseAddresses()`.

This internal usage is now based on :php:`\Symfony\Component\Mime\Address`
of the :composer:`symfony/mime` package, which TYPO3 already utilizes for
sending emails.

Impact
======

Instantiating or referencing the class :php:`Rfc822AddressesParser` will raise
a fatal PHP error.

In addition, :php:`MailUtility::parseAddresses()` now strips surrounding
double quotes from display names: parsing
:php:`'"last, first" <email@example.org>'` previously returned the display
name :php:`'"last, first"'` and now returns :php:`'last, first'`. Quoting is
re-applied automatically by :composer:`symfony/mime` when composing a mail
message.

Affected installations
======================

TYPO3 installations with third-party extensions directly using the class
:php:`Rfc822AddressesParser`. The extension scanner reports any usage as a
strong match.

Migration
=========

To parse a comma-separated list of email addresses with optional display
names, use :php:`\TYPO3\CMS\Core\Utility\MailUtility::parseAddresses()`,
which continues to work as before.

Alternatively, use the :composer:`symfony/mime` API directly to parse a
single mailbox string:

.. code-block:: php

    use Symfony\Component\Mime\Address;

    $address = Address::create('John Doe <john.doe@example.org>');
    $email = $address->getAddress();
    $name = $address->getName();

.. index:: PHP-API, FullyScanned, ext:core
