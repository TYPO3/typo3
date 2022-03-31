
.. include:: /Includes.rst.txt

======================================================
Important: #77411 - Removed extbase table column cache
======================================================

See :issue:`77411`

Description
===========

The extbase table column cache `extbase_typo3dbbackend_tablecolumns`,
which was used to store all database fields of all database tables,
hass been removed.

The associated configuration variable :php:`$GLOBALS[TYPO3_CONF_VARS][SYS][caching][cacheConfigurations][extbase_typo3dbbackend_tablecolumns]` can safely be removed.

.. index:: Database, ext:extbase, LocalConfiguration
