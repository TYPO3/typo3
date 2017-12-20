.. include:: ../../Includes.txt

==================================================================================
Feature: #79438 - Add configuration option to disable validation of stored records
==================================================================================

See :issue:`79438`


Description
===========

Two configuration options have been added to the Install Tool, which are used when saving records
using the DataHandler.

The first option allows to disable the check if the contents of a record match the data that should
have been written after saving it to the database for the whole TYPO3 installation. This allows a
speed-up especially when inserting/copying large amounts of data at the cost of possible differences,
if values could not be written as requested. This may happen, if e.g. data types do not match or
columns are too small to store the data.

Disable checking the stored records:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['BE']['checkStoredRecords'] = false;


If checking the stored records is enabled, the second option allows to make the comparison of the
record contents strict. If a loose comparison is made (which is the default), comparing an empty
string '' with the number 0 is not considered as an error ('' == 0), while using strict comparison
it is ('' !== 0).

Make the comparison of record contents strict:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['BE']['checkStoredRecordsLoose'] = false;


Impact
======

In TYPO3 installations where the administrator has configured the database to run in strict-mode
disabling the validation of stored records can speed-up inserts and updates by a factor of 2. With
strict-mode enabled the record will not be saved at all, if there are errors.

If the validation of stored records is disabled, the entry in the protocol (sys_log) does not
contain the record title (it is displayed as "[No title]" instead) - however table and uid are still
provided and can be used for finding the record.


Affected Installations
======================

None as default.

.. index:: Database, Backend, LocalConfiguration
