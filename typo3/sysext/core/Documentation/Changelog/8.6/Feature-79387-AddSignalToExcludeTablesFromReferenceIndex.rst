.. include:: ../../Includes.txt

==================================================================
Feature: #79387 - Add signal to exclude tables from ReferenceIndex
==================================================================

See :issue:`79387`

Description
===========

A new signal :php:`shouldExcludeTableFromReferenceIndex` is emitted in :php:`TYPO3\CMS\Core\Database\ReferenceIndex` which allows
extensions to define tables which should be excluded from ReferenceIndex.

Register the class which excludes tables in `ext_localconf.php`:

.. code-block:: php

   $dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
   $dispatcher->connect(
       \TYPO3\CMS\Core\Database\ReferenceIndex::class,
       'shouldExcludeTableFromReferenceIndex',
       \MyVendor\MyExtension\Slots\ReferenceIndexSlot::class,
       'shouldExcludeTableFromReferenceIndex'
   );

Your class could look like this:

.. code-block:: php

   namespace MyVendor\MyExtension\Slot;

   class ReferenceIndexSlot {

      /**
       * Exclude tables from ReferenceIndex which cannot contain a reference
       *
       * @param string $tableName Name of the table
       * @param bool &$excludeTable Reference to a boolean whether to exclude the table from ReferenceIndex or not
       */
      public function shouldExcludeTableFromReferenceIndex($tableName, &$excludeTable) {
         if ($tableName === 'tx_myextension_mytable') {
            $excludeTable = true;
         }
      }

   }


Impact
======

This signal allows extensions to speed up the process of maintaining the ReferenceIndex. If an extension has tables in which by
definition none of its columns can contain any relations to other records these can be excluded from the ReferenceIndex.

Only exclude tables from ReferenceIndex which do not contain any relations and never did since existing references won't be
deleted if it is excluded! There is no need to add tables without a definition in :php:`$GLOBALS['TCA]` since ReferenceIndex
only handles those.

.. index:: Database, PHP-API, Backend
