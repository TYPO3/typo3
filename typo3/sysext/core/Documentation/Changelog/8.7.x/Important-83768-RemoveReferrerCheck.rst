.. include:: /Includes.rst.txt

=========================================
Important: #83768 - Remove referrer check
=========================================

See :issue:`83768`

Description
===========

Browser vendors are considering or have already announced **not** to send the referrer URL/path in HTTP requests when
links are followed or forms are submitted due to privacy reasons. TYPO3 used the referrer as a meagre CSRF protection
for the backend. However, this has been replaced by proper CSRF protection tokens for every backend action and therefore,
the referrer check became obsolete and has been removed.

Usages of the configuration option :php:`[SYS][doNotCheckReferer]` within TYPO3 Core have been removed, as this is not
needed anymore. However, the option can still be set for extensions implementing this option.


Impact
======

Backend users will not notice any differences.


Affected Installations
======================

All installations are affected.


Migration
=========

TYPO3 extensions that use option :php:`[SYS][doNotCheckReferer]` to implement a kind of CSRF protection, should use
proper CSRF protection tokens provided by the core.

.. index:: Backend, FullyScanned
