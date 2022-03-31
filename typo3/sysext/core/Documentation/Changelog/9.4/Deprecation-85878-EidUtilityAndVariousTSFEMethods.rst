.. include:: /Includes.rst.txt

=========================================================
Deprecation: #85878 - EidUtility and various TSFE methods
=========================================================

See :issue:`85878`

Description
===========

The Utility class :php:`TYPO3\CMS\Frontend\Utility\EidUtility` has been marked as deprecated.

The following methods have been marked as deprecated:

* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->initFEuser()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->storeSessionData()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->previewInfo()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->hook_eofe()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->addTempContentHttpHeaders()`
* :php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->sendCacheHeaders()`

The following hook has been marked as deprecated:

* `$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_previewInfo']`


Impact
======

Calling any of the methods or registering a hook listener will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with custom functionality in the frontend using any of the methods, or the hook.


Migration
=========

As all functionality has been set up via PSR-15 middlewares, use a PSR-15 middleware instead.

The method :php:`storeSessionData()` should be replaced with :php:`TSFE->fe_user->storeSessionData()`.

The methods :php:`addTempContentHttpHeaders()` and :php:`sendCacheHeaders()` are now incorporated
within :php:`TSFE->processOutput()`. This function should be used, or rather add custom headers
to a PSR-15 Response object if available.

On top, the hook is superseded by the Frontend Hook `hook_eofe` which is executed in the Frontend rendering
flow directly afterwards.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
