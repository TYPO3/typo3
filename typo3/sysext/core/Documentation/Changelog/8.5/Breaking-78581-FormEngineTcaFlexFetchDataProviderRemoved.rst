.. include:: /Includes.rst.txt

================================================================
Breaking: #78581 - FormEngine TcaFlexFetch data provider removed
================================================================

See :issue:`78581`

Description
===========

The FormEngine data provider :php:`TcaFlexFetch` has been merged into data provider :php:`TcaFlexPrepare`.


Impact
======

If own registered data providers are declared to "depends" or "before" :php:`TcaFlexFetch`, the
:php:`DependencyResolver` will be unable to find it and throws an exception or sorts the own data
provider to an ambiguous place.


Affected Installations
======================

An installation is only affected in the relatively unlikely case that an own data provider declared a
dependency to :php:`TcaFlexFetch`.


Migration
=========

Move the dependency over to :php:`TcaFlexPrepare`: The two data providers have been merged into one, it
should be save for any data provider to hook in before or after :php:`TcaFlexPrepare` instead. There
is a little additional flex form processing in :php:`TcaFlexPrepare`, so the flex structure might be a
bit different. Have a look at methods :php:`removeTceFormsArrayKeyFromDataStructureElements()`
and :php:`migrateFlexformTcaDataStructureElements()` for details.

.. index:: PHP-API, FlexForm, Backend
