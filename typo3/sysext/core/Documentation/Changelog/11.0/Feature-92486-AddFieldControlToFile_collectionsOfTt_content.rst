.. include:: /Includes.rst.txt

=====================================================================
Feature: #92486 - Add field control to file_collections of tt_content
=====================================================================

See :issue:`92486`

Description
===========

The TCA configuration of the field `file_collections` of `tt_content` has been improved by adding
the field control `addRecord`.


Impact
======

The new field control allows editors to create a new file collection record without leaving the
content element of type "uploads".

.. index:: Backend, TCA, ext:frontend
