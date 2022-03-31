.. include:: /Includes.rst.txt

=============================================================
Important: #90264 - Initialize datepicker JS in external file
=============================================================

See :issue:`90264`

Description
===========

The initialization of the datepicker has been moved into an external
file residing in :file:`EXT:form/Resources/Public/JavaScript/Frontend/DatePicker.js`.
Some installations might restrict requesting public resources from :file:`/typo3/`.
Therefore, a new YAML configuration has been introduced:

:yaml:`TYPO3.CMS.Form.prototypes.standard.formElementsDefinition.DatePicker.properties.datePickerInitializationJavaScriptFile`

That way, integrators are able to move the file to a different folder
which is publicly accessible.

.. index:: ext:form
