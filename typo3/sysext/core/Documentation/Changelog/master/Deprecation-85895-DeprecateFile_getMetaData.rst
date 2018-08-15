.. include:: ../../Includes.txt

====================================================
Deprecation: #85895 - Deprecate File::_getMetaData()
====================================================

See :issue:`85895`

Description
===========

The internal method :php:`File::_getMetaData()` which is used to fetch meta data of a file has been marked as deprecated. This method has been superseded by the :php:`MetaDataAspect`.


Impact
======

Using this method will trigger a deprecation entry.


Affected Installations
======================

Any 3rd party extension calling :php:`_getMetaData()` is affected.


Migration
=========

To fetch the meta data, call :php:`$fileObject->getMetaData()->get()` instead.

.. index:: FAL, PHP-API, FullyScanned, ext:core
