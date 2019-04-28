.. include:: ../../Includes.txt

=====================================================
Deprecation: #87200 - EmailFinisher FORMAT_* constants
=====================================================

See :issue:`87200`

Description
===========

The constants :php:`EmailFinisher::FORMAT_PLAINTEXT` and :php:`EmailFinisher::FORMAT_HTML` have been deprecated and will be removed in TYPO3 11.0.


Impact
======

Accessing these constants will lead to a fatal error in TYPO3 11.0.


Affected Installations
======================

All installations which use EXT:form and directly access these constants.


Migration
=========

Do not use these constants anymore.

.. index:: PHP-API, FullyScanned, ext:form
