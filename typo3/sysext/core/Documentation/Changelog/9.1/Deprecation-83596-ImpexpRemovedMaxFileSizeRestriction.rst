.. include:: /Includes.rst.txt

=================================================================
Deprecation: #83596 - impexp: Removed "Max file size" restriction
=================================================================

See :issue:`83596`


Description
===========

When exporting files using the "Export" interface of extension
:php:`impexp`, the restriction to only export files of a certain
maximum size has been removed.


Impact
======

On PHP level, one class property has been marked as deprecated and is unused now:

* :php:`TYPO3\CMS\Impexp\Export->maxFileSize`


Affected Installations
======================

Backend users are probably not affected much: Users of the
export module usually set the 'max file size' value high
enough to export everything they wanted already. The interface now
just misses the according input fields and exports files of all sizes.


Migration
=========

On PHP level, the extension scanner will find extensions that
still use the deprecated property.

.. index:: Backend, PHP-API, FullyScanned, ext:impexp
