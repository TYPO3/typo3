.. include:: /Includes.rst.txt

=========================================================
Deprecation: #93837 - special property of TCA type select
=========================================================

See :issue:`93837`

Description
===========

The :php:`special` property of TCA type :php:`select` was introduced to enrich the
items array with dynamic values, e.g. the available site languages or
page types.

Since this usually is exactly what an :php:`itemsProcFunc` does, all
those options are migrated to such functions, removing complexity
from the TCA :php:`select` type. As these options are mainly for internal
use in the backend user and backend usergroup records, the new
:php:`itemsProcFunc` functions are marked as :php:`@internal`. This means, they
are not considered public API and therefore not part of TYPO3s backwards
compatibility promise.

The only option which is considered public API is :php:`special=languages`,
which was already migrated to the new TCA type :php:`language` in :issue:`57082`.

Impact
======

Using the TCA property :php:`special` inside the :php:`[columns][config]`
section of columns with TCA type :php:`select` triggers a PHP :php:`E_USER_DEPRECATED` error.

When extending :php:`AbstractItemProvider` and directly calling
:php:`addItemsFromSpecial()`, also a PHP :php:`E_USER_DEPRECATED` error will be raised.
The extension scanner will also detect such calls.

Affected Installations
======================

All installations using the :php:`special` property with TCA type :php:`select` or
directly calling :php:`AbstractItemProvider->addItemsFromSpecial()`.

Migration
=========

While it's very unlikely that the :php:`special` property with another option
than :php:`languages` is used in custom extension code, you nevertheless have to
replace them with a :php:`itemsProcFunc` in such case. Either by creating
your own implementation or by copying the one from Core. Have a look at the
:php:`index_config` TCA configuration in EXT:indexed_search how this can be
achieved. You can also find detailed information about :php:`itemsProcFunc`
in the documentation_.

.. _documentation: https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/CommonProperties/ItemsProcFunc.html

.. index:: Backend, TCA, FullyScanned, ext:backend
