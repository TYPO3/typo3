.. include:: /Includes.rst.txt

===========================================================================
Deprecation: #91012 - Various hooks related to TypoScriptFrontendController
===========================================================================

See :issue:`91012`

Description
===========

The following hooks related to class :php:`TypoScriptFrontendController`
and frontend-rendering have been marked as deprecated:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-contentStrReplace']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']`

The following methods have been marked as deprecated as well, as they only
contain code relevant for executing the hooks:

* :php:`TypoScriptFrontendController->isOutputting()`
* :php:`TypoScriptFrontendController->processContentForOutput()`


Impact
======

If third-party extensions are using the hooks, a PHP :php:`E_USER_DEPRECATED` error will be triggered when the hook is executed.

Calling the two methods above will also trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with custom extensions using the hooks or mentioned above, which is common if they haven't been using
PSR-15 middlewares or other hooks instead.


Migration
=========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing']`
should be replaced by the :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-cached']` hook
to index pages. However, please note that :php:`$TSFE->content` might contain UTF-8 content now,
instead of content already converted to the defined character set related to :typoscript:`metaCharset` TypoScript property.

Since TYPO3 v9, the emitter of HTTP responses is based on PSR-7, the hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']` can be removed, as
TYPO3 can be configured via PSR-15 middlewares to define whether
page content should be emitted / rendered or not.

The hook to dynamically replace content via :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-contentStrReplace']`
is removed as it serves no purpose for TYPO3 Core anymore. If content should be dynamically modified, use a PSR-15 middleware instead.

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']` is not needed as this can be built via a PSR-15 middleware instead, and
all content is returned via the RequestHandler of TYPO3 Frontend.

Extensions using hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']` should
be converted to PSR-15 middlewares, as this allows to modify content and headers of a PSR-7 Response object.

The method :php:`TypoScriptFrontendController->isOutputting()` is obsolete and can be removed in third-party code.

The same applies to :php:`TypoScriptFrontendController->processContentForOutput()` which should only be used to trigger
legacy hooks still applied in the system.

.. index:: PHP-API, FullyScanned, ext:frontend
