.. include:: /Includes.rst.txt

.. _feature-98490-1664580564:

===============================================================================
Feature: #98490 - PSR-14 event to alter the records rendered in record listings
===============================================================================

See :issue:`98490`

Description
===========

A new PSR-14 event :php:`TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForRecordListingEvent`
has been added, which allows to alter the :php:`QueryBuilder` SQL statement
before a list of records is about to be rendered and executed in record lists
such as the list module or element browser.

Impact
======

Registering an event listeners allows to further limit or expand the list of
records shown in certain record lists to alter the records shown.

.. index:: Backend, PHP-API, ext:backend
