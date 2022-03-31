.. include:: /Includes.rst.txt

================================================================
Feature: #89746 - Custom icon for record browser button in forms
================================================================

See :issue:`89746`

Description
===========

The record browser is used in form definitions e.g. to configure the :yaml:`ContentElement` element or the :yaml:`Redirect` finisher.

The icons of the buttons which trigger the record browser are now configurable using the new option :yaml:`iconIdentifier`:

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formElementsDefinition:
               ContentElement:
                 formEditor:
                   editors:
                     # ...
                     300:
                       identifier: contentElement
                       # ...
                       browsableType: tt_content
                       iconIdentifier: mimetypes-x-content-text
                       propertyPath: properties.contentElementUid
                       # ...


Impact
======

The icons for the record browser button can now be customized.

.. index:: Backend, ext:form
