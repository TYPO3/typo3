.. include:: /Includes.rst.txt

.. _deprecation-100596-1681478199:

=============================================
Deprecation: #100596 - GeneralUtility::_GET()
=============================================

See :issue:`100596`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::_GET()` has
been marked as deprecated and should not be used any longer.

Modern code should access GET and POST data from the PSR-7 :php:`ServerRequestInterface`,
and should avoid accessing superglobals :php:`$_GET` directly. This also avoids
future side-effects when using sub-requests. Some :php:`GeneralUtility` related
helper methods like :php:`_GET()` violate this, using them is considered a technical
debt. They are being phased out.



Impact
======

Calling the method from PHP code will log a PHP deprecation level entry,
the method will be removed with TYPO3 v13.


Affected installations
======================

TYPO3 installations with third-party extensions using :php:`GeneralUtility::_GET()`
are affected, typically in TYPO3 installations which
have been migrated to the latest TYPO3 Core versions and
haven't been adapted properly yet.

The extension scanner will find usages with a strong match.


Migration
=========

:php:`GeneralUtility::_GET()` is a helper method that retrieves
incoming HTTP `GET` query arguments and returns the value.

The same result can be achieved by retrieving arguments from the request object.
An instance of the PSR-7 :php:`ServerRequestInterface` is handed over to
controllers by TYPO3 Core's PSR-15 :php:`\TYPO3\CMS\Core\Http\RequestHandlerInterface`
and middleware implementations, and is available in various related scopes
like the frontend :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer`.

Typical code:

..  code-block:: php

    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // Before
    $value = GeneralUtility::_GET('tx_scheduler');

    // After
    $value = $request->getQueryParams()['tx_scheduler']) ?? null;


.. index:: PHP-API, FullyScanned, ext:core
