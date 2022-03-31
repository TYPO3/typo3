.. include:: /Includes.rst.txt

==========================================================
Deprecation: #86279 - Various Hooks and PSR-15 Middlewares
==========================================================

See :issue:`86279`

Description
===========

The new PSR-15-based middleware concept allows for a more fine-grained "hooking" mechanism when enhancing the HTTP
Request or Response object.

The following hooks have therefore been marked as deprecated:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-PostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preBeUser']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['postBeUser']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest']`

On top, some middlewares have only been introduced in order to execute these hooks, or due to, and are marked for
internal use:

* :file:`typo3/cms-core/normalized-params-attribute`
* :file:`typo3/cms-backend/legacy-document-template`
* :file:`typo3/cms-backend/output-compression`
* :file:`typo3/cms-backend/response-headers`
* :file:`typo3/cms-frontend/timetracker`
* :file:`typo3/cms-frontend/preprocessing`
* :file:`typo3/cms-frontend/eid`
* :file:`typo3/cms-frontend/content-length-headers`
* :file:`typo3/cms-frontend/tsfe`
* :file:`typo3/cms-frontend/output-compression`
* :file:`typo3/cms-frontend/prepare-tsfe-rendering`
* :file:`typo3/cms-frontend/shortcut-and-mountpoint-redirect`

As these middlewares are marked as internal, it is recommended not to reference them directly, as these might get removed
in TYPO3 v10.


Impact
======

Making use of one of the hooks in an extension will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 instances with extensions using any of the hooks.


Migration
=========

Use a custom PSR-15 middleware instead.

.. index:: PHP-API, FullyScanned, ext:frontend
