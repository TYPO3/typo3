
.. include:: ../../Includes.txt

=================================================
Breaking: #61783 - Removed deprecated mailing API
=================================================

See :issue:`61783`

Description
===========

The deprecated methods to send email are removed.
This includes the :code:`MailUtility::mail()` method, the mail delivery substitution API and the :code:`plainMailEncoded()` methods.

Impact
======

Any call to :code:`MailUtility::mail()` or :code:`GeneralUtility::plainMailEncoded()` will result in a fatal error.
The option :code:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery']`
does not have any effect.


Affected installations
======================

Any installation using an extension still using the deprecated API will fail.

Migration
=========

Use the :code:`\TYPO3\CMS\Core\Mail\Mailer` API for sending email.


.. index:: PHP-API
