.. include:: /Includes.rst.txt

.. _deprecation-109544-1775761298:

=============================================================================
Deprecation: #109544 - GeneralUtility::sanitizeLocalUrl() needs PSR-7 request
=============================================================================

See :issue:`109544`

Description
===========

Calling :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::sanitizeLocalUrl()` without
passing the current PSR-7 request as second argument is deprecated. The method
previously resolved host and site information via
:php:`GeneralUtility::getIndpEnv()`, which falls back to server superglobals.
Passing the request explicitly allows the method to read this information from
:php:`\TYPO3\CMS\Core\Http\NormalizedParams` instead.


Impact
======

Calling :php:`GeneralUtility::sanitizeLocalUrl()` with only one argument triggers
a PHP :php:`E_USER_DEPRECATED` error.


Affected installations
======================

All installations that call :php:`GeneralUtility::sanitizeLocalUrl()` without
passing a :php:`\Psr\Http\Message\ServerRequestInterface` as second argument.

The extension scanner will detect affected usages as a strong match.


Migration
=========

Pass the current PSR-7 request as second argument:

..  code-block:: php

    // Before
    $url = GeneralUtility::sanitizeLocalUrl($url);

    // After
    $url = GeneralUtility::sanitizeLocalUrl($url, $request);

.. index:: PHP-API, FullyScanned, ext:core
