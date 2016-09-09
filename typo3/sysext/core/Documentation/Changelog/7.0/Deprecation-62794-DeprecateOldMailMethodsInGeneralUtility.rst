
.. include:: ../../Includes.txt

====================================================
Deprecation: #62794 - Mail methods in GeneralUtility
====================================================

See :issue:`62794`

Description
===========

The following methods of the class \TYPO3\CMS\Core\Utility\GeneralUtility have been marked as deprecated:

- quoted_printable()
- encodeHeader()
- substUrlsInPlainText()

Impact
======

The methods were used together with the old mail API and are now obsolete. Deprecation warnings will be triggered if used.

Affected installations
======================

Installations that still use those methods will trigger deprecations warnings.


Migration
=========

Code that still uses these methods should be refactored to the mail API using
TYPO3\CMS\Core\Mail\Mailer class.
