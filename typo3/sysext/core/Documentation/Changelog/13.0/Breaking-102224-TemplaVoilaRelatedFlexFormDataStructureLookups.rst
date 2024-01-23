.. include:: /Includes.rst.txt

.. _breaking-102224-1697983588:

======================================================================
Breaking: #102224 - TemplaVoila related FlexForm dataStructure lookups
======================================================================

See :issue:`102224`

Description
===========

The following TCA config options for :php:`'type' = 'flex'` column fields are
not handled anymore:

* :php:`['config']['ds_pointerField_searchParent']`
* :php:`['config']['ds_pointerField_searchParent_subField']`
* :php:`['config']['ds_tableField']`

The following related exception classes have been removed and are no longer thrown:

* :php:`\TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowException`
* :php:`\TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowLoopException`
* :php:`\TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowRootException`
* :php:`\TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidPointerFieldValueException`


Impact
======

When dealing with TCA type :php:`flex` fields, there needs to be a "data structure" that
defines which fields are rendered when editing the record. The default is looking up
the data structure using the :php:`['ds']['default']` value.

Multiple different data structures can be defined, so there is a strategy to find the
data structure relevant for current record. For table :sql:`tt_content`, this is
defined using :php:`ds_pointerField`, which determines the specific data structure based
on the combination of the fields :sql:`CType` and :sql:`list_type`.

There have been more sophisticated lookup mechanisms based on the TCA config options
:php:`ds_pointerField_searchParent`, :php:`ds_pointerField_searchParent_subField`
and :php:`ds_tableField`. Those lookup mechanisms have been removed with TYPO3 v13.


Affected installations
======================

Instances with extensions having :php:`flex` fields using one of the TCA options
:php:`ds_pointerField_searchParent`, :php:`ds_pointerField_searchParent_subField`
or :php:`ds_tableField` will fail to retrieve their data structure. Most likely,
an exception will be thrown when editing such records.

Those three fields have been implemented long ago for heavily flex form driven
instances based on "TemplaVoila" (TV). This detail never found broader acceptance in
not-TV driven instances.

Instances not driven by one of the TemplaVoila forks are most likely not affected
by this change. Instances actively using TemplaVoila forks may be affected, but
those forks seem to implement the data structure lookup on their own already,
affected instances should wait for their templavoila maintainers to catch up.


Migration
=========

There are appropriate events that allow manipulating the data structure
lookup logic in class :php:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools`.
Those can be used to re-implement the logic that has been removed from TYPO3
Core if needed.


.. index:: FlexForm, PHP-API, TCA, PartiallyScanned, ext:core
