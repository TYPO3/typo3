.. include:: /Includes.rst.txt

===========================================================================================
Feature: #86967 - Allow fetching uid of a LazyLoadingProxy without loading the object first
===========================================================================================

See :issue:`86967`

Description
===========

A method :php:`getUid()` has been added to class :php:`\TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy` which
allows for fetching the uid of the proxied object without fetching the object data itself from the database.

Impact
======

The method :php:`getUid()` can be used to fetch the uid of objects for a quick comparison with other objects of the same
type which increases the performance of such comparisons a lot.

.. index:: PHP-API, ext:extbase
