..  include:: /Includes.rst.txt

..  _deprecation-109551-1775924599:

=======================================================
Deprecation: #109551 - GeneralUtility::getIndpEnv()
=======================================================

See :issue:`109551`

Description
===========

Method :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv()` has been deprecated.

The method abstracts server environment variables and was used to obtain request-related
data from PHP superglobals such as the current host, URI, or site path. This information is
reliably available via :php:`\TYPO3\CMS\Core\Http\NormalizedParams`, which is attached
as attribute to the PSR-7 request.


Impact
======

Calling :php:`GeneralUtility::getIndpEnv()` triggers a PHP
:php:`E_USER_DEPRECATED` error.


Affected installations
======================

All installations that call :php:`GeneralUtility::getIndpEnv()` directly.

The extension scanner will detect affected usages as a strong match.


Migration
=========

Replace calls to :php:`GeneralUtility::getIndpEnv()` with the corresponding
:php:`\TYPO3\CMS\Core\Http\NormalizedParams` getter. A
:php:`NormalizedParams` instance is available as an attribute of the PSR-7
request:

..  code-block:: php

    // Before
    $siteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
    $host = GeneralUtility::getIndpEnv('HTTP_HOST');

    // After
    $normalizedParams = $request->getAttribute('normalizedParams');
    $siteUrl = $normalizedParams->getSiteUrl();
    $host = $normalizedParams->getHttpHost();

..  index:: PHP-API, FullyScanned, ext:core
