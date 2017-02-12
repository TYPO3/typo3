.. include:: ../../Includes.txt

=============================================================
Feature: #79531 - EXT:form - Add multiselect inspector editor
=============================================================

See :issue:`79531`

Description
===========

A new inspector editor, i.e. a new field type of the form editor, has been added.
If applied, multi-select fields can be added to the inspector.
A multi-select field allows the selection of multiple meta properties for a field
and stores them in the defined property path.

For example:
If you have a file upload element in your form, until now you could only select a single
mime type restriction. With the new multi-select option, the mime-type field was converted
and you can now select multiple mime-types (for example docx, doc and odt together).

.. index:: Backend, ext:form