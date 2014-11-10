====================================================
Deprecation: #62794 - Mail methods in GeneralUtility
====================================================

Description
===========

The following methods of the class \TYPO3\CMS\Core\Utility\GeneralUtility are deprecated:

 * quoted_printable()
 * encodeHeader()
 * substUrlsInPlainText()

Impact
======

The methods were used together with the old mail API and are obsolete now. Deprecation warnings will be triggered if used.

Affected installations
======================

Installations that still use those methods will trigger deprecations warnings.


Migration
=========

Code that still uses these methods should be refactored to the mail API using
TYPO3\CMS\Core\Mail\Mailer class.
