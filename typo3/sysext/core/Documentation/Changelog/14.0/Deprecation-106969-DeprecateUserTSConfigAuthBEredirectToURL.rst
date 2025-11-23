..  include:: /Includes.rst.txt

..  _deprecation-106969-1750853865:

====================================================================
Deprecation: #106969 - Deprecate User TSConfig auth.BE.redirectToURL
====================================================================

See :issue:`106969`

Description
===========

The User TSConfig option :tsconfig:`auth.BE.redirectToURL` has been marked as
deprecated and will be removed in TYPO3 v15.0.

Impact
======

Using the deprecated User TSConfig option triggers a deprecation-level log
entry and will stop working in TYPO3 v15.0.

Affected installations
======================

TYPO3 installations using the User TSConfig option
:tsconfig:`auth.BE.redirectToURL` are affected.

Migration
=========

If a redirect after a successful backend login is required, create a custom
PSR-15 middleware to handle the redirection.

..  index:: Backend, NotScanned, ext:backend
