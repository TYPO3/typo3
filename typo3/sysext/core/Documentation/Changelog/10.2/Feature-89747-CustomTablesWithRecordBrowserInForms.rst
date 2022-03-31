.. include:: /Includes.rst.txt

============================================================
Feature: #89747 - Custom tables with record browser in forms
============================================================

See :issue:`89747`

Description
===========

The record browser in forms now accepts arbitrary custom tables if configured accordingly.

The option :yaml:`browsableType` of the :yaml:`Inspector-Typo3WinBrowserEditor` can be set to an arbitrary table:

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formElementsDefinition:
               MyCustomElement:
                 formEditor:
                   editors:
                     # ...
                     300:
                       identifier: myRecord
                       # ...
                       browsableType: tx_myext_mytable
                       propertyPath: properties.myRecordUid
                       # ...

Similar to the :yaml:`ContentElement` form element custom logic must be added in the matching frontend partial to actually display something for the selected record.


Impact
======

Form definitions can be set up to allow editors the selection of arbitrary database records and then render them using custom logic.

.. index:: Backend, ext:form
