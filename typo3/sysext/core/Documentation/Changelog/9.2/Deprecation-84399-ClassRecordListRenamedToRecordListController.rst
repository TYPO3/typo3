.. include:: /Includes.rst.txt

======================================================================
Deprecation: #84399 - Class RecordList renamed to RecordListController
======================================================================

See :issue:`84399`

Description
===========

The PHP class :php:`TYPO3\CMS\Recordlist\RecordList` has been renamed to
:php:`TYPO3\CMS\Recordlist\Controller\RecordListController`


Impact
======

The old class name has been registered as class alias and will still work.
Old class name usage however is discouraged and should be avoided, the
alias will vanish with core version 10.


Affected Installations
======================

Extensions that hook into the list module may be affected if type hinting
with the old classes as :php:`$parentObject`.

The extension scanner will find affected extensions using the old class name.


Migration
=========

Use new class name instead.

.. index:: Backend, PHP-API, FullyScanned, ext:recordlist
