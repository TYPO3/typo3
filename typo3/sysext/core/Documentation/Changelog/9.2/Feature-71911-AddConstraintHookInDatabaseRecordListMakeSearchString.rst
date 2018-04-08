.. include:: ../../Includes.txt

==============================================================================
Feature: #71911 - Add constraint hook in  DatabaseRecordList->makeSearchString
==============================================================================

See :issue:`71911`

Description
===========

A newly introduced hook in `DatabaseRecordList->makeSearchString` allows to modify the constraints which are applied to
the search string.

Example
=======

An example implementation could look like this:

:file:`EXT:my_site/ext_localconf.php`

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class]['makeSearchStringConstraints'][1313131313] =
      \MyVendor\MySite\Hooks\DatabaseRecordListHook::class . '->makeSearchStringConstraints';


:file:`EXT:my_site/Classes/Hooks/DatabaseRecordListHook.php`

.. code-block:: php

   namespace MyVendor\MySite\Hooks;

   class DatabaseRecordListHook
   {
      public function makeSearchStringConstraints(
         \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder
         array $constraints,
         string $searchString,
         string $table,
         int $currentPid,
      ) {
         return $constraints;
      }
   }

.. index:: Backend, Database, PHP-API