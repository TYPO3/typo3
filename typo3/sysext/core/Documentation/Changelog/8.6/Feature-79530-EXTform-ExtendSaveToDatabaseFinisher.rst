.. include:: ../../Includes.txt

============================================================
Feature: #79530 - EXT:form -  Extend SaveToDatabase finisher
============================================================

See :issue:`79530`

Description
===========

The SaveToDatabase finisher has been extended by the following functions:

Perform multiple database operations
------------------------------------

You can set options as an array to perform multiple database operations.

.. code-block:: yaml

     finishers:
       -
         identifier: SaveToDatabase
         options:
           -
             table: 'my_table'
             mode: insert
             databaseColumnMappings:
               some_column:
                 value: 'cool'
           -
             table: 'my_other_table'
             mode: update
             whereClause:
               pid: 1
             databaseColumnMappings:
               some_other_column:
                 uid_foreign: '{SaveToDatabase.insertedUids.0}'

Access the inserted uids from previous database inserts
-------------------------------------------------------

You can access the inserted uids via `{SaveToDatabase.insertedUids.<theArrayKeyNumberWithinOptions>}`.
If you perform an insert operation, the value of the inserted database row will be stored within the FinisherVariableProvider.
`<theArrayKeyNumberWithinOptions>` references to the numeric key from the `options` array within which the insert operation is executed.

Add a special option value '{__currentTimestamp}'
-------------------------------------------------

You can write `{__currentTimestamp}` as an option value which returns the current timestamp.

.. code-block:: yaml

     finishers:
       -
         identifier: SaveToDatabase
         options:
           -
             table: 'my_table'
             mode: insert
             databaseColumnMappings:
               tstamp:
                 value: '{__currentTimestamp}'

Add a variable container object which is passed through all finishers
---------------------------------------------------------------------

There is a simple data storage object available within the `\TYPO3\CMS\Form\Domain\Finishers\FinisherContext`.
You can access this from within a finisher with

.. code-block:: php

   $this->finisherContext->getFinisherVariableProvider()

Each finisher can write and/or read data from this object.
All data has to be prefixed with the finisher identifier, so you can determine what data from which finisher you want.

Prototype to add some data:

.. code-block:: php

   $this->finisherContext->getFinisherVariableProvider()->add(
      $this->shortFinisherIdentifier,
      'some.data',
      $yourData
   );

Prototype to get some data:

.. code-block:: php

   $otherFinisherData = $this->finisherContext->getFinisherVariableProvider()->get(
      'SomeFinisherIdentifier',
      'some.data'
   );

You can access this data within the form definition:

For example, a finisher with identifier 'SomeFinisherIdentifier' writes data with the key 'some.data'

.. code-block:: yaml

     finishers:
       -
         identifier: SaveToDatabase
         options:
           -
             table: 'my_table'
             mode: insert
             databaseColumnMappings:
               tstamp:
                 value: '{SomeFinisherIdentifier.some.key}'

You can read more about the configuration options within the SaveToDatabase inline documentation.
Please see `\TYPO3\CMS\Form\Domain\Finishers\SaveToDatabaseFinisher`.

.. index:: Frontend, ext:form