.. include:: /Includes.rst.txt

=========================================================
Feature: #93668 - Possibility to configure Symfony mailer
=========================================================

See :issue:`93668`

Description
===========

The install tool has now the possibility to configure the Symfony mailer with
DSN. Symfony provides different mail transports like SMTP, sendmail or many 3rd
party email providers like AWS SES, Gmail, MailChimp, Mailgun and more. You can
find all supported providers in the
`Symfony documentation <https://symfony.com/doc/current/mailer.html>`__.

In the module :guilabel:`Admin tools > Settings` go to the card
:guilabel:`Configure Installation-Wide Options` and open the dialog.
Select :guilabel:`Mail` and set :php:`[MAIL][transport]` to :php:`dsn`.

Additionally set :php:`[MAIL][dsn]` like described in the Symfony documentation.

Examples:

*  :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['dsn'] = "smtp://user:pass@smtp.example.com:25"`
*  :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['dsn'] = "sendmail://default"`


Impact
======

If :php:`[MAIL][transport]` is set to :php:`dsn` all mails are sent with your
configured DSN.

.. index:: LocalConfiguration, ext:core
