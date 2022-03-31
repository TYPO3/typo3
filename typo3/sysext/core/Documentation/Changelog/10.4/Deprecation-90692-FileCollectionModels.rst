.. include:: /Includes.rst.txt

===========================================
Deprecation: #90692 - FileCollection models
===========================================

See :issue:`90692`

Description
===========

The following classes have been marked as deprecated:

- :php:`\TYPO3\CMS\Extbase\Domain\Model\StaticFileCollection`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\FolderBasedFileCollection`
- :php:`\TYPO3\CMS\Extbase\Domain\Model\AbstractFileCollection`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\StaticFileCollectionConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\FolderBasedFileCollectionConverter`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverter\AbstractFileCollectionConverter`

The classes were marked as internal and never contained any logic. Therefore and in order to streamline the codebase of Extbase, the files will be removed with TYPO3 11.0.


Impact
======

Using any of the mentioned classes will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any TYPO3 installation with a third-party extension using the classes.


Migration
=========

Copy the classes to your own extension and adopt the usages.

.. index:: PHP-API, FullyScanned, ext:extbase
