.. include:: ../../Includes.txt

====================================================================
Deprecation: #93837 - Deprecated special property of TCA type select
====================================================================

See :issue:`93837`

Description
===========

The `special` property of TCA type `select` was introduced to enrich the
items array with dynamic value, e.g. the available site languages or
page types.

Since this usually is exactly what an :php:`itemsProcFunc` does, all
those options are migrated to such functions, removing complexity
from the TCA `select` type. As these options are mainly for internal
use in `be_users` and `be_groups`, the new :php:`itemsProcFunc` functions
are marked as :php:`@internal`. This means, they are not considered public
API and therefore not part of TYPO3's backwards-compatibility promise.

The only option which is considered public API is `special=languages`,
which was already migrated to the new TCA type `language` in :issue:`57082`.

Impact
======

Using the TCA property `special` inside the :php:`[columns][config]`
section of columns with TCA type `select` adds a deprecation message to
the deprecation log.

When extending :php:`AbstractItemProvider` and directly calling
:php:`addItemsFromSpecial()`, a deprecation message will be added to
the deprecation log. The extension scanner will also detect such calls.

Affected Installations
======================

All installations using the `special` property with TCA type `select` or
directly calling :php:`AbstractItemProvider->addItemsFromSpecial()`.

Migration
=========

While it's very unlikely that the `special` property with another option
than `languages` is used in custom extension code, you nevertheless have to
replace them with a :php:`itemsProcFunc` in such case. Either by creating
your own implementation or by copying the one from Core. Have a look at the
:php:`index_config` TCA configuration in EXT:indexed_search how this can be
achieved. You can also find detailed information about :php:`itemsProcFunc`
in the documentation_.

.. _documentation: https://docs.typo3.org/m/typo3/reference-tca/master/en-us/ColumnsConfig/CommonProperties/ItemsProcFunc.html

.. index:: Backend, TCA, FullyScanned, ext:backend
