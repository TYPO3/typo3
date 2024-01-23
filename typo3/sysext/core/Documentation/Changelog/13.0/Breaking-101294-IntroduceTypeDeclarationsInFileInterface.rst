.. include:: /Includes.rst.txt

.. _breaking-101294-1688885539:

================================================================
Breaking: #101294 - Introduce type declarations in FileInterface
================================================================

See :issue:`101294`

Description
===========

Return and param type declarations have been introduced for all methods stubs
of :php:`\TYPO3\CMS\Core\Resource\FileInterface`.


Impact
======

In consequence, all implementations of :php:`\TYPO3\CMS\Core\Resource\FileInterface` need
to reflect those changes and add the same return and param type declarations.

In case, any of the Core implementations are extended, overridden methods might need
to be adjusted. The Core classes, implementing :php:`\TYPO3\CMS\Core\Resource\FileInterface`, are:

- :php:`\TYPO3\CMS\Core\Resource\AbstractFile`
- :php:`\TYPO3\CMS\Core\Resource\File`
- :php:`\TYPO3\CMS\Core\Resource\FileReference`
- :php:`\TYPO3\CMS\Core\Resource\ProcessedFile`


Affected installations
======================

Only those installations that implement :php:`\TYPO3\CMS\Core\Resource\FileInterface` directly
or that extend any of those mentioned core implementations.


Migration
=========

Return and param type declarations have to be synced with the ones of the interface.

.. index:: FAL, PHP-API, NotScanned, ext:core
