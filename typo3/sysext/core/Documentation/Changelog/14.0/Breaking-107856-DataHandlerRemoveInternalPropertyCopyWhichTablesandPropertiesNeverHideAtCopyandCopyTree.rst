..  include:: /Includes.rst.txt

..  _breaking-107856-1763715381:

=========================================================================================================================
Breaking: #107856 - DataHandler: Remove internal property `copyWhichTables`and properties `neverHideAtCopy`and `copyTree`
=========================================================================================================================

See :issue:`107856`

Description
===========

The following public properties of the PHP class `TYPO3\CMS\Core\DataHandling\DataHandler` have been removed:

* `copyWhichTables`
* `neverHideAtCopy`
* `copyTree`


Impact
======

Accessing or setting the properties will throw a PHP warning and have no effect anymore.


Affected installations
======================

Any installation working with the public properties in a third-party extension.


Migration
=========

The configuration values `neverHideAtCopy ` and `copyTree` are directly read from the
backend user :php:`BE_USER` object. To modify them, use the following values instead:

..  code-block:: php
    // Before
    DataHandler->neverHideAtCopy
    // After
    DataHandler->BE_USER->uc['neverHideAtCopy']

    // Before
    DataHandler->copyTree
    // After
    DataHandler->BE_USER->uc['copyLevels']

To retain database consistency, the list of tables to be copied now only relied on permissions
of the given backend user. If the user has admin access, all tables will be copied if needed.
If not, all tables with access will be copied.


..  index:: PHP-API, FullyScanned, ext:core
