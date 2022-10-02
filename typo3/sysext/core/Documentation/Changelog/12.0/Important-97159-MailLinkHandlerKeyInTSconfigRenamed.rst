.. include:: /Includes.rst.txt

.. _important-97159:

=============================================================
Important: #97159 - MailLinkHandler key in TSconfig renamed
=============================================================

See :issue:`97159`

Description
===========

The key for the :php:`MailLinkHandler` in the :typoscript:`TCEMAIN.linkHandler.`
TSconfig has been renamed from :typoscript:`mail` to :typoscript:`email`.

This is done to be consistent with the identifier, used for the
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['linkHandler']` registration,
as well as the value of the :php:`LinkService::TYPE_EMAIL` constant.

Update any usage of this key in your extension code.

.. index:: TSConfig, ext:backend
