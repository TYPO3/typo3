.. include:: /Includes.rst.txt

=======================================================================================================
Feature: #90945 - PSR-14 event for LocalizationController when reading records/columns to be translated
=======================================================================================================

See :issue:`90945`

Description
===========

A new PSR-14 event :php:`\TYPO3\CMS\Backend\Controller\Event\AfterPageColumnsSelectedForLocalizationEvent`
has been added and will be dispatched after records and columns are collected in the :php`LocalizationController`.

The event receives:

* The default columns and columnsList built by :php:`LocalizationController`
* The list of records that were analyzed to create the columns manifest
* The parameters received by the :php`LocalizationController`

The event allows changes to:

* the columns
* the columnsList


Impact
======

This allows third party code to read or manipulate the "columns manifest" that gets displayed in the
translation modal when a user has clicked the ``Translate`` button in the page module, by implementing
a listener for the :php:`\TYPO3\CMS\Backend\Controller\Event\AfterPageColumnsSelectedForLocalizationEvent` event.

.. index:: Backend, ext:backend
