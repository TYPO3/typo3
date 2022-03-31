
.. include:: /Includes.rst.txt

==============================================================================
Feature: #68395 - Allow real copies of content elements into foreign languages
==============================================================================

See :issue:`68395`

Description
===========

A new button has been added to each column in the "Page" module which allows "real" copies of content element into a language.
This allows to create copies from any language into the destination.
References, like FAL records, become independent records and are not related to the original record, as there is no parent anymore.


Impact
======

The button will be either displayed as as standalone button if a page has no records in the default language or as a
split button if there are records in the default language.

Creating real copies will cause the loss of any functionality between the copy and the default language (e.g. diff),
as the copy is not defined as child of the element where it was copied from.


.. index:: Backend
