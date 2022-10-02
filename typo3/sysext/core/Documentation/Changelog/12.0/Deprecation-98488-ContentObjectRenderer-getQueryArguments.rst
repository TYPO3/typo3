.. include:: /Includes.rst.txt

.. _deprecation-98488-1664576976:

==============================================================
Deprecation: #98488 - ContentObjectRenderer->getQueryArguments
==============================================================

See :issue:`98488`

Description
===========

The public method
:php:`TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->getQueryArguments()`
has been marked as deprecated.

Impact
======

Calling the method directly via PHP will trigger a PHP deprecation warning.

Affected installations
======================

TYPO3 installations with custom third-party extensions calling this method directly,
which is highly unlikely.

Migration
=========

Use LinkFactory directly to create links with the typolink configuration option
:typoscript:`typolink.addQueryString = untrusted` to create links with the same behaviour.

.. index:: Frontend, TypoScript, FullyScanned, ext:frontend
