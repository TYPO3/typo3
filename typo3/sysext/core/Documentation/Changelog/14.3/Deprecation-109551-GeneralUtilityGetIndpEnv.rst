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
data from PHP superglobals, such as the current host, URI, and site path. This information is
reliably available via :php:`\TYPO3\CMS\Core\Http\NormalizedParams`, which is attached
as an attribute to the PSR-7 request.


Impact
======

Calling :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv()` triggers
a PHP :php:`E_USER_DEPRECATED` error.


Affected installations
======================

All installations that call
:php:`\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv()` directly.

The extension scanner will detect affected usages as a strong match.


Migration
=========

Replace calls to :php:`\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv()` with the corresponding
:php:`\TYPO3\CMS\Core\Http\NormalizedParams` getter. A
:php-short:`\TYPO3\CMS\Core\Http\NormalizedParams` instance is available as an attribute of the PSR-7
request:

..  code-block:: php

    // Before
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $siteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
    $host = GeneralUtility::getIndpEnv('HTTP_HOST');

    // After
    use TYPO3\CMS\Core\Http\NormalizedParams;

    /** @var NormalizedParams $normalizedParams */
    $normalizedParams = $request->getAttribute('normalizedParams');
    $siteUrl = $normalizedParams->getSiteUrl();
    $host = $normalizedParams->getHttpHost();

..  index:: PHP-API, FullyScanned, ext:core
