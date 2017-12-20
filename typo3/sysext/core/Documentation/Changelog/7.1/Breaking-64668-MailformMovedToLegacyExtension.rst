
.. include:: ../../Includes.txt

=====================================================================
Breaking: #64668 - Content Element mailform moved to legacy extension
=====================================================================

See :issue:`64668`

Description
===========

The fallback "mailform" functionality, containing the `FORM` ContentObject, the submission logic for sending mailform
and the content element (CType=mailform) has been moved to the legacy extension "compatibility6". This mailform
was available when the "Form" extension, introduced in TYPO3 CMS 4.7, was not installed.

The following options have been marked for deprecation:

.. code-block:: php

	$TYPO3_CONF_VARS][FE][secureFormmail]
	$TYPO3_CONF_VARS][FE][strictFormmail]
	$TYPO3_CONF_VARS][FE][formmailMaxAttachmentSize]

The following methods within TypoScriptFrontendController have been removed:

.. code-block:: php

	protected checkDataSubmission()
	protected sendFormmail()
	public extractRecipientCopy()
	public codeString()
	protected roundTripCryptString()


Impact
======

Mailform elements are missing and not rendered in the frontend anymore unless EXT:compatibility6 is loaded.


Affected installations
======================

Any installation using the fallback "mailform" Content Element, the FrontendDataSubmissionController or the FORM
Content Object directly will break. Additionally, any third party extension using the TypoScriptFrontendController
methods directly will stop working with a fatal error.

Migration
=========

For TYPO3 CMS 7, installing EXT:compatibility6 brings back the existing functionality. For the long term the affected
installations should migrate to a different, better suited solution for sending mails and building forms.


.. index:: PHP-API, Backend, Frontend, LocalConfiguration, ext:form, TypoScript
