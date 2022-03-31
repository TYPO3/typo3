.. include:: /Includes.rst.txt

====================================================
Deprecation: #85895 - Deprecate File::_getMetaData()
====================================================

See :issue:`85895`

Description
===========

The internal method :php:`TYPO3\CMS\Core\Resource\File::_getMetaData()` which is used to fetch meta data of a file
has been marked as deprecated. This method has been superseded by the :php:`TYPO3\CMS\Core\Resource\MetaDataAspect`.


Impact
======

Using this method will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Any 3rd party extension calling :php:`:php:`TYPO3\CMS\Core\Resource\File::_getMetaData()` is affected.


Migration
=========

To fetch the meta data, call :php:`$fileObject->getMetaData()->get()` instead.

.. index:: FAL, PHP-API, FullyScanned, ext:core
