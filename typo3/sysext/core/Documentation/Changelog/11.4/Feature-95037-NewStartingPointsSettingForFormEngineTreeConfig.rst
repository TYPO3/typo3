.. include:: /Includes.rst.txt

======================================================================
Feature: #95037 - New startingPoints setting for FormEngine treeConfig
======================================================================

See :issue:`95037`

Description
===========

The TCA option :php:`treeConfig` used in :php:`renderType=selectTree` and :php:`type=category` has a new
setting :php:`startingPoints` that allows to set multiple records as roots for tree
records.


Impact
======

The setting takes a CSV value, e.g. `2,3,4711`, which takes records of the pids
`2`, `3` and `4711` into account and creates a tree of these records.

Additionally, each value used in :php:`startingPoints` may be fed from a site
configuration by using the :php:`###SITE:###` syntax.

Example:

.. code-block:: yaml

   # Site config
   base: /
   rootPageId: 1
   categories:
      root: 123


.. code-block:: php

   // Example TCA config
   'config' => [
       'treeConfig' => [
           'startingPoints' => '1,2,###SITE:categories.root###',
       ],
   ],

This will evaluate to :php:`'startingPoints' => '1,2,123'`.

.. index:: Backend, ext:backend
