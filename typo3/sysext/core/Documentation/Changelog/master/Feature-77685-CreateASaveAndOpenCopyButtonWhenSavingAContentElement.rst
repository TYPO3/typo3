.. include:: ../../Includes.txt

==================================================================================
Feature: #77685 - Create a save and open copy button when saving a content element
==================================================================================

See :issue:`77685`

Description
===========

This patch adds a "clone content element" icon next to the save icon in the edit record form for already persisted reccords. If there are not persisted changes when pressing the button a modal appears, providing the following 3 options: abort, clone the content element without saving the current changes, save the changes and clones the record afterwards. The copy of the record will by put right below the record itself.
After saving, the edit record form opens for the copied element.


Impact
======

Editors are able to make a duplicate of a record with just a single click. They don't have to copy & paste.

.. index:: Backend