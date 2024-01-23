.. include:: /Includes.rst.txt

.. _breaking-101327-1689092559:

===================================================
Breaking: #101327 - Harden FileInterface::getSize()
===================================================

See :issue:`101327`

Description
===========

A return type declaration has been added to the method stub :php:`\TYPO3\CMS\Core\Resource\FileInterface::getSize()`.
As a consequence, implementations of said method, :php:`\TYPO3\CMS\Core\Resource\AbstractFile::getSize()`
and :php:`\TYPO3\CMS\Core\Resource\FileReference::getSize()` received return type declarations as well.

Also, :php:`\TYPO3\CMS\Core\Resource\AbstractFile::getSize()` has been adjusted to actually just
return an integer. Previously, it returned :php:`null`, if the actual size could not be gathered. It now returns
:php:`0` in that case.


Impact
======

Code, that calls  :php:`\TYPO3\CMS\Core\Resource\AbstractFile::getSize()` through derivatives like
:php:`\TYPO3\CMS\Core\Resource\File::getSize()` might be adjusted to not respect :php:`null` any more.

Implementations (classes) that implement :php:`\TYPO3\CMS\Core\Resource\FileInterface`, have to
adjust the return type of the method :php:`getSize()` to match the contract.


Affected installations
======================

Installations that implement  :php:`\TYPO3\CMS\Core\Resource\FileInterface` or that call
:php:`\TYPO3\CMS\Core\Resource\FileInterface::getSize()` via derivatives.


Migration
=========

Adjust the return type and possible :php:`null` checks.

.. index:: FAL, PHP-API, NotScanned, ext:core
