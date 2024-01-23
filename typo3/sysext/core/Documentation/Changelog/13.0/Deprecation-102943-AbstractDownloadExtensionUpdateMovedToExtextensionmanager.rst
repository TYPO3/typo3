.. include:: /Includes.rst.txt

.. _deprecation-102943-1706271208:

====================================================================================
Deprecation: #102943 - AbstractDownloadExtensionUpdate moved to ext:extensionmanager
====================================================================================

See :issue:`102943`

Description
===========

The following upgrade wizard related classes have been moved from EXT:install
to EXT:extensionmanager:

* :php:`\TYPO3\CMS\Install\Updates\AbstractDownloadExtensionUpdate`, new name
  :php:`\TYPO3\CMS\Extensionmanager\Updates\AbstractDownloadExtensionUpdate`
* :php:`\TYPO3\CMS\Install\Updates\ExtensionModel`, new name
  :php:`\TYPO3\CMS\Extensionmanager\Updates\ExtensionModel`

Class aliases have been established for TYPO3 v13, which will be removed with
TYPO3 v14.

Impact
======

Extensions that extend :php:`AbstractDownloadExtensionUpdate` and
then most likely use :php:`ExtensionModel` as well, should update the namespace.


Affected installations
======================

Few instances should be affected: There are a couple of extensions that try to
extend the upgrade range from two major Core versions, and ship older
upgrade wizards. Apart from that, the abstract is most likely rarely used.
Consuming extensions should adapt the namespace, the old class names will stop
working with TYPO3 v14.

The extension scanner will find usages as strong match.


Migration
=========

Adapt the namespaces in extension classes that extend :php:`AbstractDownloadExtensionUpdate`
from :php:`\TYPO3\CMS\Install` to :php:`\TYPO3\CMS\Extensionmanager`.

.. index:: PHP-API, FullyScanned, ext:extensionmanager
