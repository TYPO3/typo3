..  include:: /Includes.rst.txt

..  _deprecation-106969-1750853865:

====================================================================
Deprecation: #106969 - Deprecate User TSConfig auth.BE.redirectToURL
====================================================================

See :issue:`106969`

Description
===========

The User TSConfig option  :php:`auth.BE.redirectToURL` has been marked as
deprecated and will be removed in TYPO3 v15.0.


Impact
======

Using the deprecated User TSConfig option will raise a deprecation level log
error and will stop working in TYPO3 v15.0.


Affected installations
======================

TYPO3 installations using User TSConfig :php:`auth.BE.redirectToURL`.


Migration
=========

If a redirect after a successful backend login is required, it is recommended
to create a custom PSR-15 middleware, which handles the redirection.

..  index:: Backend, NotScanned, ext:backend
