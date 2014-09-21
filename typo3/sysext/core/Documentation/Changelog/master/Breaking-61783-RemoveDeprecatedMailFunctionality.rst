=================================================
Breaking: #61783 - Removed deprecated mailing API
=================================================

Description
===========

The deprecated methods to send email are removed.
This includes the MailUtility::mail() method, the mail delivery substitution API and the plainMailEncoded() methods.

Impact
======

Any call to MailUtility::mail() or GeneralUtility::plainMailEncoded() will result in a fatal error.
The option $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/utility/class.t3lib_utility_mail.php']['substituteMailDelivery']
does not have any effect.


Affected installations
======================

Any installation using an extension still using the deprecated API will fail.

Migration
=========

Use the \TYPO3\CMS\Core\Mail\Mailer API for sending email.
