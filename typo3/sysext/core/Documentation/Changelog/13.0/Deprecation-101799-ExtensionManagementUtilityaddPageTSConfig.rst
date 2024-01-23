.. include:: /Includes.rst.txt

.. _deprecation-101799-1693397542:

====================================================================
Deprecation: #101799 - ExtensionManagementUtility::addPageTSConfig()
====================================================================

See :issue:`101799`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig()`
has been marked as deprecated in TYPO3 v13 and will be removed with TYPO3 v14.

The global configuration option :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig']`
has been marked as deprecated in TYPO3 v13, will be ignored and removed from instance configuration
files as silent upgrade with TYPO3 v14.


Impact
======

Setting default page TSconfig using :php:`ExtensionManagementUtility::addPageTSConfig()`
in :file:`ext_localconf.php` files has been superseded by
:ref:`Automatic inclusion of page TSconfig of extensions <feature-96614>` with
TYPO3 v12 already. The old way has been deprecated now, extensions should switch to
the new functionality by placing default page TSconfig in :file:`Configuration/page.tsconfig`
files.


Affected installations
======================

Instances with extensions using :php:`ExtensionManagementUtility::addPageTSConfig()`
or directly extending :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig']`
are affected: Using :php:`ExtensionManagementUtility::addPageTSConfig()` triggers a
deprecation level log message. The extension scanner will find usages of
:php:`ExtensionManagementUtility::addPageTSConfig()` as strong match.


Migration
=========

Add default page TSconfig to a :file:`Configuration/page.tsconfig` file within an
extension and remove calls to :php:`ExtensionManagementUtility::addPageTSConfig()`.
Placing default page TSconfig in :file:`Configuration/page.tsconfig` files is
available since TYPO3 v12, extensions aiming for v12 and v13 compatibility can
simply switch over to the new way.


.. index:: LocalConfiguration, PHP-API, TSConfig, FullyScanned, ext:core
