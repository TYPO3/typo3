.. include:: /Includes.rst.txt

.. _important-88158-1668433741:

=================================================
Important: #88158 - Replaced moment.js with luxon
=================================================

See :issue:`88158`

Description
===========

The JavaScript library `luxon` is added to TYPO3 as a replacement for `moment.js`
that is `declared legacy`_. All code shipped by TYPO3 is migrated to `luxon`.

Albeit shipped `moment.js` is not considered being public API, it is worth
mentioning that said library is removed with TYPO3 v12.1.

.. _declared legacy: https://momentjs.com/docs/#/-project-status/

.. index:: Backend, JavaScript, NotScanned, ext:core
