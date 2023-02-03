.. include:: /Includes.rst.txt

.. _feature-97923-1673529192:

=====================================================================================
Feature: #97923 - Improve performance and usability while editing sys_file_collection
=====================================================================================

See :issue:`97923`

Description
===========

The two fields :sql:`storage` and :sql:`folder` of the :sql:`sys_file_collection`
table are now combined into the new field :sql:`folder_identifier`. The field
contains the so-called combined identifier in the format `storage:folder`,
where `storage` is the :sql:`uid` of the corresponding :sql:`sys_file_storage`
record and `folder` the absolute path to the folder, e.g. `1:/user_upload`.

An upgrade wizard is in place to migrate the two fields of the existing records
to the new field.

The TCA type `folder` is now used in the backend editing form to improve the
usability on selecting the corresponding folder via the folder selector, when
using the file collections with type `folder`.


Impact
======

Editing :sql:`sys_file_collection` records for the record type `folder` in the
backend is improved. Instead of selecting the storage first, reloading the form
and selecting the folder in a possibly large list afterwards, are users now
able to select the folder using the folder selector in a single step.

This additionally improves the performance of the backend form, especially for
storages with a huge amount of folders.

Also working with such records is improved, since only one field has to be
taken into account.

.. index:: Backend, ext:core
