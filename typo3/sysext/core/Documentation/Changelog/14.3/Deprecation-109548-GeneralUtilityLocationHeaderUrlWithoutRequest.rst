..  include:: /Includes.rst.txt

..  _deprecation-109548-1775851081:

================================================================================
Deprecation: #109548 - GeneralUtility::locationHeaderUrl() without PSR-7 request
================================================================================

See :issue:`109548`

Description
===========

Calling :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl()` without
providing a PSR-7 :php:`\Psr\Http\Message\ServerRequestInterface` as the second argument is deprecated.

The method previously resolved the current host and request directory via
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv()`, which
hides an implicit dependency on server globals. The method signature has been
extended to accept an explicit PSR-7 request object, which should be passed instead.

Impact
======

A PHP :php:`E_USER_DEPRECATED` error is triggered when
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl()` is called
without a :php-short:`\Psr\Http\Message\ServerRequestInterface` argument.

Affected installations
======================

All installations that call
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl()` without
passing :php-short:`\Psr\Http\Message\ServerRequestInterface` as the second argument.

The extension scanner will detect affected usages as a strong match.

Migration
=========

Pass the current PSR-7 request as the second argument to
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl()`.

..  code-block:: diff

    use TYPO3\CMS\Core\Utility\GeneralUtility;
    + use Psr\Http\Message\ServerRequestInterface;

    - $url = GeneralUtility::locationHeaderUrl($path);
    + $url = GeneralUtility::locationHeaderUrl($path, $request);

The PSR-7 request is available in various places, for example as an argument
in controller actions, via :php:`$GLOBALS['TYPO3_REQUEST']` in legacy contexts,
and via :php-short:`\Psr\Http\Message\ServerRequestInterface` method parameters.

..  index:: PHP-API, FullyScanned, ext:core
