.. include:: /Includes.rst.txt

.. _breaking-101469-1690528614:

==================================================================
Breaking: #101469 - Introduce type declarations in FolderInterface
==================================================================

See :issue:`101469`

Description
===========

Return and param type declarations have been introduced for all methods stubs
of :php:`\TYPO3\CMS\Core\Resource\FolderInterface`.


Impact
======

In consequence, all implementations of :php:`\TYPO3\CMS\Core\Resource\FolderInterface`
need to reflect those changes and add the same return and param type declarations.

In case, any of the Core implementations are extended, overridden methods might need to
be adjusted. The Core classes, implementing :php:`\TYPO3\CMS\Core\Resource\FolderInterface`
are:

- :php:`\TYPO3\CMS\Core\Resource\Folder`
- :php:`\TYPO3\CMS\Core\Resource\InaccessibleFolder`


Affected installations
======================

All installations that implement :php:`\TYPO3\CMS\Core\Resource\FolderInterface`
or that extend either :php:`\TYPO3\CMS\Core\Resource\Folder` or
:php:`\TYPO3\CMS\Core\Resource\InaccessibleFolder`.


Migration
=========

Add the same param and return type declarations the interface does.


.. index:: FAL, PHP-API, NotScanned, ext:core
