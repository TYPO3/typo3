.. include:: ../../Includes.txt

======================================================================================
Feature: #78332 - Allow setting a default replyTo-email-address for notification-mails
======================================================================================

See :issue:`78332`

Description
===========

Two new LocalConfiguration settings have been introduced:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToAddress']
	$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToName']

Also a new function to build a mail address for SwiftMailer from these settings is introduced:

.. code-block:: php

	MailUtility::getSystemReplyTo()

If no default reply-to address is set this function will return an empty array.

This function is used in :php:`ContentObjectRenderer::sendNotifyEmail()` to set a ReplyTo address in case no address is
supplied in the function parameters.
In other places where notifications are sent for e.g. (failed) login attempts, reports and where the notification uses
the system from address this function is also used.


Impact
======

It's now possible to set a reply-to address for notification mails from TYPO3. Extensions can also use this system
reply-to address by calling :php:`MailUtility::getSystemReplyTo()`.

.. index:: LocalConfiguration, PHP-API
