.. include:: /Includes.rst.txt

.. _feature-97922-1657706124:

================================================================================
Feature: #97922 - Improve performance and usability while editing sys_filemounts
================================================================================

See :issue:`97922`

Description
===========

The two fields :sql:`base` and :sql:`path` of the :sql:`sys_filemounts` table
are now combined in one new field :sql:`identifier`. The field contains the so
called combined identifier in the format `base:path`, where "base" is the
storage ID and "path" the path to the folder, e.g. `1:/user_upload`.

An upgrade wizard is in place, migrating the two fields of existing records
into the new field.

The TCA type `folder` is used in the backend form to select the entry point.

Impact
======

Editing :sql:`sys_filemounts` records in the backend is improved. Instead of
selecting the storage first, reloading the form and selecting the entry point
in a  possibly large list  afterwards, are users now able to select the entry
point using the folder browser in a single step. This additionally improves
the performance of the backend form, especially for storages with a huge
amount of folders.

.. index:: Backend, TCA, ext:core
