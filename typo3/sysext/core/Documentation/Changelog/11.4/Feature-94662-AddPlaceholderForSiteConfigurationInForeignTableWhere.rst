.. include:: /Includes.rst.txt

===============================================================================
Feature: #94662 - Add placeholder for site configuration in foreign_table_where
===============================================================================

See :issue:`94662`

Description
===========

The :php:`foreign_table_where` setting in TCA allows some old marker-based
placeholder to customize the query. The best place to define site-dependent
settings is the site configuration, which now can be used within
:php:`foreign_table_where`.

To access a configuration value the following syntax is available:

* `###SITE:<KEY>###` - <KEY> is your setting name from site config e.g. `###SITE:rootPageId###`
* `###SITE:<KEY>.<SUBKEY>###` - an array path notation is possible. e.g. `###SITE:mySetting.categoryPid###`

Example:
--------

.. code-block:: php

    ...
    'fieldConfiguration' => [
        'foreign_table_where' => ' AND ({#sys_category}.{#uid} = ###SITE:rootPageId### OR {#sys_category}.{#pid} = ###SITE:mySetting.categoryPid###) ORDER BY {#sys_category}.{#title} ASC',
    ],
    ...

.. index:: Backend, FlexForm, TCA, NotScanned, ext:backend
