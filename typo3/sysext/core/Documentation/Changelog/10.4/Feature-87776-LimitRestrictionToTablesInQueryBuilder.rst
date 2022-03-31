.. include:: /Includes.rst.txt

==============================================================
Feature: #87776 - Limit Restriction to table/s in QueryBuilder
==============================================================

See :issue:`87776`

Description
===========

In some cases it is needed to apply restrictions only to a certain table.
With the new :php:`\TYPO3\CMS\Core\Database\Query\Restriction\LimitToTablesRestrictionContainer`
it is possible to apply restrictions to a query only for a given set of tables, or to be precise, table aliases.
Since it is a restriction container, it can be added to the restrictions of the query builder and
it can hold restrictions itself. The restrictions it holds can be limited to tables like this:


Example implementation:
-----------------------

.. code-block:: php

   $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
   $queryBuilder->getRestrictions()
       ->removeByType(HiddenRestriction::class)
       ->add(
           GeneralUtility::makeInstance(LimitToTablesRestrictionContainer::class)
               ->addForTables(GeneralUtility::makeInstance(HiddenRestriction::class), ['tt'])
       );
   $queryBuilder->select('tt.uid', 'tt.header', 'sc.title')
       ->from('tt_content', 'tt')
       ->from('sys_category', 'sc')
       ->from('sys_category_record_mm', 'scmm')
       ->where(
           $queryBuilder->expr()->eq('scmm.uid_foreign', $queryBuilder->quoteIdentifier('tt.uid')),
           $queryBuilder->expr()->eq('scmm.uid_local', $queryBuilder->quoteIdentifier('sc.uid')),
           $queryBuilder->expr()->eq('tt.uid', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
       );


In this example the HiddenRestriction is only applied to :sql:`tt` table alias of :sql:`tt_content`.

Furthermore it is possible to restrict the complete set of restrictions of a query builder to a
given set of table aliases.

.. code-block:: php

   $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
   $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
   $queryBuilder->getRestrictions()->limitRestrictionsToTables(['c2']);
   $queryBuilder
      ->select('c1.*')
      ->from('tt_content', 'c1')
      ->leftJoin('c1', 'tt_content', 'c2', 'c1.parent_field = c2.uid')
      ->orWhere($queryBuilder->expr()->isNull('c2.uid'), $queryBuilder->expr()->eq('c2.pid', $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)));

Which will result in:

.. code-block:: sql

   SELECT "c1".*
   FROM "tt_content" "c1"
   LEFT JOIN "tt_content" "c2" ON c1.parent_field = c2.uid
   WHERE (("c2"."uid" IS NULL) OR ("c2"."pid" = 1)) AND ("c2"."hidden" = 0))

Impact
======

It is now easily possible to add restrictions that are only applied to certain tables/ table aliases,
by using :php:`\TYPO3\CMS\Core\Database\Query\Restriction\LimitToTablesRestrictionContainer`.

.. index:: Database, ext:core, API
