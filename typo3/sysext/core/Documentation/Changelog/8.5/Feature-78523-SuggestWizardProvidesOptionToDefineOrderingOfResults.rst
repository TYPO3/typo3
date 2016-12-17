.. include:: ../../Includes.txt

==============================================================================
Feature: #78523 - Suggest wizard provides option to define ordering of results
==============================================================================

See :issue:`78523`

Description
===========

It is now possible to set the ordering of results delivered by the suggest wizard.

The new option is called php:`orderBy => 'somefield ASC'` and can hold the usual SQL order-by definition.

Example TCA configuration for `EXT:news` suggest wizard returning the related articles sorted by datetime:

.. code-block:: php

   'config' => [
       'type' => 'group',
       'internal_type' => 'db',
       'allowed' => 'tx_news_domain_model_news',
       'foreign_table' => 'tx_news_domain_model_news',
       'MM_opposite_field' => 'related_from',
       'size' => 5,
       'minitems' => 0,
       'maxitems' => 100,
       'MM' => 'tx_news_domain_model_news_related_mm',
       'wizards' => [
           'suggest' => [
               'type' => 'suggest',
               'default' => [
                   'searchWholePhrase' => true,
                   'addWhere' => ' AND tx_news_domain_model_news.uid != ###THIS_UID###',
                   'orderBy => 'datetime DESC',
               ]
           ],
       ],
   ]

.. index:: Backend, TCA
