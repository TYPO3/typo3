.. include:: /Includes.rst.txt

============================================================
Deprecation: #88850 - ContentObjectRenderer::sendNotifyEmail
============================================================

See :issue:`88850`

Description
===========

The method :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::sendNotifyEmail()`
which has been used to send mails has been marked as deprecated.


Impact
======

Using this method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All 3rd party extensions calling
:php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::sendNotifyEmail()` are affected.


Migration
=========

To send a mail, use the :php:`\TYPO3\CMS\Core\Mail\MailMessage`-API

.. code-block:: php

    $email = GeneralUtility::makeInstance(MailMessage::class)
         ->to(new Address('katy@domain.tld'), new Address('john@domain.tld', 'John Doe'))
         ->subject('This is an example email')
         ->text('This is the plain-text variant')
         ->html('<h4>Hello John.</h4><p>Enjoy a HTML-readable email. <marquee>We love TYPO3</marquee>.</p>');

    $email->send();

.. index:: PHP-API, FullyScanned, ext:frontend
