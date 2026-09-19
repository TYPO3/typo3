..  include:: /Includes.rst.txt

..  _feature-75037-1758268800:

======================================================
Feature: #75037 - Markers in treeConfig startingPoints
======================================================

See :issue:`75037`

Description
===========

The TCA setting :php:`treeConfig.startingPoints`, used by
:php:`renderType=selectTree` and :php:`type=category` fields, now resolves the
same markers that are already available in :php:`foreign_table_where`:

*  :php:`###CURRENT_PID###` - the page id the record is stored on
*  :php:`###SITEROOT###` - the uid of the site root page of the current rootline
*  :php:`###PAGE_TSCONFIG_ID###` - a value set via page TSconfig
*  :php:`###PAGE_TSCONFIG_IDLIST###` - a comma separated list of values set via
   page TSconfig

Example:

..  code-block:: php

    'config' => [
        'type' => 'select',
        'renderType' => 'selectTree',
        'foreign_table' => 'tx_myextension_domain_model_category',
        'treeConfig' => [
            'parentField' => 'parent',
            'startingPoints' => '###CURRENT_PID###',
        ],
    ],

The markers :php:`###PAGE_TSCONFIG_ID###` and :php:`###PAGE_TSCONFIG_IDLIST###`
are fed from page TSconfig:

..  code-block:: typoscript

    TCEFORM.tx_myextension_domain_model_record.categories.PAGE_TSCONFIG_IDLIST = 12,34

Markers may be combined with static uids and with the already available
:php:`###SITE:###` syntax, for example
:php:`'startingPoints' => '1,###CURRENT_PID###,###SITE:categories.root###'`.

Impact
======

Starting points of :php:`selectTree` and :php:`category` trees can be made
dependent on the record's page, the site root or page TSconfig, without
resorting to a page TSconfig override of the whole
:typoscript:`config.treeConfig.startingPoints` setting.

Markers which can not be resolved - for instance :php:`###SITEROOT###` outside
of a site - are removed from the list instead of falling back to the page tree
root.

The markers are resolved after a possible page TSconfig override, so they may
also be used in
:typoscript:`TCEFORM.<table>.<field>.config.treeConfig.startingPoints`.

.. index:: Backend, TCA, ext:backend
