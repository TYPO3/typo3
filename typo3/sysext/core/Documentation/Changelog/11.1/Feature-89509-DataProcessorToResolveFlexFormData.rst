.. include:: /Includes.rst.txt

=========================================================
Feature: #89509 - Data Processor to resolve FlexForm data
=========================================================

See :issue:`89509`

Description
===========

TYPO3 offers "FlexForms", which can be used to store data within an XML
structure inside a single DB column. Since this information could also be
relevant in the view, a new data processor
:php:`TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor` is added. It
converts the FlexForm data of a given field into a Fluid readable array.

Options
-------

:`fieldName`:  Field name of the column the FlexForm data is stored in (default: :sql:`pi_flexform`).
:`as`:         The variable to be used within the result (default: :php:`flexFormData`).

Example of a minimal TypoScript configuration
---------------------------------------------

.. code-block:: typoscript

   10 = TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor

The converted array can be accessed within the Fluid template
with the :html:`{flexFormData}` variable.

Example of an advanced TypoScript configuration
-----------------------------------------------

.. code-block:: typoscript

   10 = TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor
   10 {
       fieldName = my_flexform_field
       as = myOutputVariable
   }

The converted array can be accessed within the Fluid template
with the :html:`{myOutputVariable}` variable.

Example with a custom sub processor
------------------------------------

.. code-block:: typoscript

   10 = TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor
   10 {
       fieldName = my_flexform_field
       as = myOutputVariable
       dataProcessing {
          10 = Vendor\MyExtension\DataProcessing\CustomFlexFormProcessor
       }
   }


Impact
======

It's now possible to access the FlexForm data of a field in a
readable way in the Fluid template.

.. index:: Fluid, TypoScript, Frontend
