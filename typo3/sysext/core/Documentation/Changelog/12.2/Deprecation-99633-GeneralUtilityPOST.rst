.. include:: /Includes.rst.txt

.. _deprecation-99633-1674121794:

=============================================
Deprecation: #99633 - GeneralUtility::_POST()
=============================================

See :issue:`99633`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::_POST()` has
been marked deprecated and should not be used any longer.

Modern code should access GET and POST data from the PSR-7
:php:`\Psr\Http\Message\ServerRequestInterface`, and should avoid accessing
super-globals :php:`$_GET` and :php:`$_POST`
directly. This will avoid future side-effects when using sub-requests. Some
:php:`GeneralUtility` related helper methods like :php:`_POST()` violate this,
using them is considered a technical debt. They are being phased out.


Impact
======

Calling the method from PHP code will trigger a PHP deprecation notice.


Affected installations
======================

TYPO3 installations with third-party extensions using :php:`GeneralUtility::_POST()`
are affected. This typically occurs in TYPO3 installations which
have been migrated to latest TYPO3 Core versions and
haven't been adapted properly yet.

The extension scanner will find usages with a strong match.


Migration
=========

:php:`GeneralUtility::_POST()` is a helper method that retrieves
incoming HTTP body parameters / `POST` parameters and returns the value.

The same result can be achieved by retrieving arguments from the request object.
An instance of the PSR-7 :php:`ServerRequestInterface` is handed over to
controllers by TYPO3 Core's PSR-15 :php:`\TYPO3\CMS\Core\Http\RequestHandlerInterface`
and middleware implementations, and is available in various related scopes
like the frontend :php:`\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer`.

Typical code:

..  code-block:: php

    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // Before
    $value = GeneralUtility::_POST('tx_scheduler');

    // After
    $value = $request->getParsedBody()['tx_scheduler']);

.. index:: Backend, PHP-API, FullyScanned, ext:backend
