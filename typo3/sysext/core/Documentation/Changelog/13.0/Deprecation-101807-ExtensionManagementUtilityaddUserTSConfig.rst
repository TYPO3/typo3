.. include:: /Includes.rst.txt

.. _deprecation-101807-1693474000:

====================================================================
Deprecation: #101807 - ExtensionManagementUtility::addUserTSConfig()
====================================================================

See :issue:`101807`

Description
===========

The method :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig()`
has been marked as deprecated in TYPO3 v13 and will be removed with TYPO3 v14.

The global configuration option :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig']`
has been marked as deprecated in TYPO3 v13, will be ignored and removed from instance configuration
files as silent upgrade with TYPO3 v14.


Impact
======

Setting default user TSconfig using :php:`ExtensionManagementUtility::addUserTSConfig()`
in :file:`ext_localconf.php` files has been superseded by
:ref:`Automatic inclusion of user TSconfig of extensions <feature-101807-1693473782>`. The
old way has been deprecated, extensions should switch to the new functionality by placing
default user TSconfig in :file:`Configuration/user.tsconfig` files.


Affected installations
======================

Instances with extensions using :php:`ExtensionManagementUtility::addUserTSConfig()`
or directly extending :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultUserTSconfig']`
are affected: Using :php:`ExtensionManagementUtility::addUserTSConfig()` triggers a
deprecation level log message. The extension scanner will find usages of
:php:`ExtensionManagementUtility::addUserTSConfig()` as strong match.


Migration
=========

Add default user TSconfig to a :file:`Configuration/user.tsconfig` file within an
extension and remove calls to :php:`ExtensionManagementUtility::addUserTSConfig()`.

Extensions with compatibility for both TYPO3 v12 and v13 should keep the old
way and switch to the new way when v12 support is dropped.


.. index:: LocalConfiguration, PHP-API, TSConfig, FullyScanned, ext:core
