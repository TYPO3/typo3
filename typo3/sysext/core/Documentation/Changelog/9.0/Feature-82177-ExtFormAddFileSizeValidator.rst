.. include:: /Includes.rst.txt

=========================================
Feature: #82177 - add file size validator
=========================================

See :issue:`82177`

Description
===========

A new ExtbaseValidator called :php:`FileSizeValidator` has been added which is able to validate a file
resource regarding its file size. This validator has 2 options:

- minimum

The minimum file size to accept. Use the format <size>B|K|M|G. For example: 10M means 10 megabytes.

- maximum

The maximum file size to accept. Use the format <size>B|K|M|G. For example: 10M means 10 megabytes.

Please keep in mind that the maximum file size also depends on php.ini settings.

Example configuration:

.. code-block:: yaml

    validators:
      -
        identifier: FileSize
        options:
          minimum: 1M
          maximum: 10M

This validator can also be used within the form editor for file and image upload elements.

Impact
======

A file upload element can be validated regarding its file size. It is possible to add, remove and
edit the FileSizeValidator for file upload elements like `ImageUpload` and `FileUpload` within the
form editor.

.. index:: Backend, Frontend, ext:form
