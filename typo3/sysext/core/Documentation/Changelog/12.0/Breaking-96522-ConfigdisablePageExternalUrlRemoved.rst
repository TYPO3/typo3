.. include:: /Includes.rst.txt

.. _breaking-96522:

========================================================
Breaking: #96522 - config.disablePageExternalUrl removed
========================================================

See :issue:`96522`

Description
===========

The TypoScript setting :typoscript:`config.disablePageExternalUrl` has been removed.

In previous versions, it allowed to have third-party extensions such as
"jumpurl" handle the redirect, and/or do tracking like extensions "sys_stat"
did back in 2006. TYPO3 Core did not do a redirect itself then when this
option was activated.

Impact
======

This option is removed, meaning that TYPO3 Core will always handle a deep link
to a page with an external URL as a redirect, which has been the default
behaviour for TYPO3 installations anyways.

Affected Installations
======================

TYPO3 installations explicitly setting this option, which is highly unlikely,
as modern solutions - even jumpurl - use middlewares already since TYPO3 v9.

Migration
=========

Migrate to a PSR-15 middleware in your own extension to mimic the same behavior,
if this option was actually useful for anybody in recent years.

.. index:: Frontend, TypoScript, NotScanned, ext:frontend
