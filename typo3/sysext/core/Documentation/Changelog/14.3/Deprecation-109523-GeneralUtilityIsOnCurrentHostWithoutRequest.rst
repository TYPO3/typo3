..  include:: /Includes.rst.txt

..  _deprecation-109523-1775680564:

==============================================================================
Deprecation: #109523 - GeneralUtility::isOnCurrentHost() without PSR-7 request
==============================================================================

See :issue:`109523`

Description
===========

Calling :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::isOnCurrentHost()` without
providing a PSR-7 :php:`\Psr\Http\Message\ServerRequestInterface` as the
second argument is deprecated.

The method previously resolved the current host via
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv()`,
which hides an implicit dependency on server globals. The method signature has been
extended to accept an explicit PSR-7 request object, which should be passed instead.

Impact
======

A PHP :php:`E_USER_DEPRECATED` error is triggered when
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::isOnCurrentHost()` is called without a
:php-short:`\Psr\Http\Message\ServerRequestInterface` argument.

Affected installations
======================

All installations with third-party extensions that call
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::isOnCurrentHost()` with
only one argument.

Migration
=========

Pass the current PSR-7 request as the second argument to
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::isOnCurrentHost()`.

Before:

..  code-block:: php

    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $isOnCurrentHost = GeneralUtility::isOnCurrentHost($url);

After:

..  code-block:: php

    use Psr\Http\Message\ServerRequestInterface;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $isOnCurrentHost = GeneralUtility::isOnCurrentHost($url, $request);

The PSR-7 request is available in various places, for example as an argument
in controller actions, via :php:`$GLOBALS['TYPO3_REQUEST']` in legacy contexts,
and via :php-short:`\Psr\Http\Message\ServerRequestInterface` method parameters.

..  index:: PHP-API, FullyScanned, ext:core
