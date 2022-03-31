
.. include:: /Includes.rst.txt

======================================================================================================
Feature: #68771 - Add contentObject functionality to form MailPostProcessor and introduce replyToEmail
======================================================================================================

See :issue:`68771`

Description
===========

If the form configuration is defined by TypoScript the following items for the MailPostProcessor
in ext:form have contentObject functionality now:

- subject
- senderEmail
- senderName
- recipientEmail
- ccEmail
- replyToEmail (newly introduced, replyToEmailField as fallback)
- priority
- organization

This feature is not available when building the form with the help of
the wizard. The functionality can only be used be setting up the form
via TypoScript.

Usage
=====

In the mail postProcessor configuration you could do something like this
(depending on the names of the form elements):

.. code-block:: typoscript

	replyToEmail = TEXT
	replyToEmail {
		data = GP:tx_form_form|tx_form|e-mail
		htmlSpecialChars = 1
	}
	subject = TEXT
	subject {
		data = GP:tx_form_form|tx_form|subject
		htmlSpecialChars = 1
		noTrimWrap = |Mail from Form: ||
	}


.. index:: TypoScript, ext:form
