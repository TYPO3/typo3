.. include:: /Includes.rst.txt

.. _important-104477-1722069728:

=========================================================================
Important: #104477 - Remove hyphen prefix from sys_log's data field entry
=========================================================================

See :issue:`104477`

Description
===========

The :php:`\TYPO3\CMS\Core\Log\Writer\DatabaseWriter` is used to write logs into
the database table `sys_log`. Additional log information data is persisted
in the field `data` and has been prefixed with a `-` until now.
As this makes it harder to parse the data, which is JSON-encoded anyway,
the prefix has been removed.

Beware that existing log entries are not migrated automatically.
This leads to a mixed structure in the database table until old records are cleaned.
(TYPO3 itself does not interpret the content of the field.)

.. index:: Database, ext:core
