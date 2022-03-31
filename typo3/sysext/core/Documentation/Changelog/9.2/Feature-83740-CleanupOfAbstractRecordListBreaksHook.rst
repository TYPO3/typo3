.. include:: /Includes.rst.txt

===========================================================
Feature: #83740 - Cleanup of AbstractRecordList breaks hook
===========================================================

See :issue:`83740`

Description
===========

A new hook in :php:`DatabaseRecordList` and :php:`PageLayoutView` allows modify the current database query.

Register the hook via

* php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class]['modifyQuery']`
* php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Backend\View\PageLayoutView::class]['modifyQuery']`

in the extensions :file:`ext_localconf.php` file.

Example
=======

An example implementation could look like this:

:file:`EXT:my_site/ext_localconf.php`

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::class]['modifyQuery'][1313131313] =
      \MyVendor\MySite\Hooks\DatabaseRecordListHook::class . '->modifyQuery';


:file:`EXT:my_site/Classes/Hooks/DatabaseRecordListHook.php`

.. code-block:: php

   namespace MyVendor\MySite\Hooks;

   class DatabaseRecordListHook
   {
      public function modifyQuery(
         array $parameters,
         string $table,
         int $pageId,
         array $additionalConstraints,
         array $fieldList,
         \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder
      ) {
         // modify $queryBuilder
      }
   }

.. index:: Backend, Database, PHP-API
