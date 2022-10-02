.. include:: /Includes.rst.txt

.. _important-97145:

================================================================================
Important: #97145 - Serialized log_data in sys_log migrated to JSON-encoded data
================================================================================

See :issue:`97145`

Description
===========

TYPO3's `sys_log` database table contains a field :sql:`log_data`, which
is used by TYPO3 Core internally to store additional information for log
details. This field has been previously filled with a serialized string
(PHP function :php:`serialize()`) and has now been migrated to a
JSON-encoded field (PHP function :php:`json_encode()`).

An Upgrade Wizard migrates the field values of existing `sys_log` entries
to the new format automatically.

Impact
======

Additional information are now stored as JSON-encoded data in the
:sql:`log_data` field of table `sys_log`.

.. index:: Database, ext:core
