.. include:: /Includes.rst.txt

.. _deprecation-109519-1775665165:

=============================================================
Deprecation: #109519 - BackendUtility item list label methods
=============================================================

See :issue:`109519`

Description
===========

The following methods in
:php:`\TYPO3\CMS\Backend\Utility\BackendUtility` have been deprecated:

- :php:`getLabelFromItemlist()`
- :php:`getLabelFromItemListMerged()`
- :php:`getLabelsFromItemsList()`

Their logic has been moved to the new
:php:`\TYPO3\CMS\Core\Schema\SchemaLabelResolver` class, which
provides proper dependency injection support.

Impact
======

Calling these methods will trigger a PHP :php:`E_USER_DEPRECATED`
error. The methods will be removed in TYPO3 v15.0.

Affected installations
======================

TYPO3 installations with extensions that call
:php:`BackendUtility::getLabelFromItemlist()`,
:php:`BackendUtility::getLabelFromItemListMerged()` or
:php:`BackendUtility::getLabelsFromItemsList()`.

Migration
=========

Replace calls to :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getLabelFromItemlist()`
with :php:`\TYPO3\CMS\Core\Schema\SchemaLabelResolver->getLabelForFieldValue()`.

Before:

.. code-block:: php

    use TYPO3\CMS\Backend\Utility\BackendUtility;

    $label = BackendUtility::getLabelFromItemlist(
        $table, $column, $value, $row
    );

After:

.. code-block:: php

    use TYPO3\CMS\Core\Schema\SchemaLabelResolver;

    $label = $this->schemaLabelResolver->getLabelForFieldValue(
        $table, $column, $value, $row
    );

Replace calls to :php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getLabelFromItemListMerged()`
with :php:`\TYPO3\CMS\Core\Schema\SchemaLabelResolver->getLabelForFieldValue()`.

Before:

.. code-block:: php

    use TYPO3\CMS\Backend\Utility\BackendUtility;

    $label = BackendUtility::getLabelFromItemListMerged(
        $pageId, $table, $column, $value, $row
    );

After:

.. code-block:: php

    use TYPO3\CMS\Backend\Utility\BackendUtility;
    use TYPO3\CMS\Core\Schema\SchemaLabelResolver;

    $columnTsConfig = BackendUtility::getPagesTSconfig($pageId)
        ['TCEFORM.'][$table . '.'][$column . '.'] ?? [];
    $label = $this->schemaLabelResolver->getLabelForFieldValue(
        $table, $column, $value, $row, $columnTsConfig
    );

Replace calls to :php:`BackendUtility::getLabelsFromItemsList()`
with :php:`\TYPO3\CMS\Core\Schema\SchemaLabelResolver->getLabelsForFieldValues()`.
Note that the new method returns an array of raw labels instead of a
comma-separated translated string — callers must handle translation
and joining themselves.

Before:

.. code-block:: php

    use TYPO3\CMS\Backend\Utility\BackendUtility;

    $labels = BackendUtility::getLabelsFromItemsList(
        $table, $column, $keyList, $columnTsConfig, $row
    );

After:

.. code-block:: php

    use TYPO3\CMS\Core\Schema\SchemaLabelResolver;

    $labels = $this->schemaLabelResolver->getLabelsForFieldValues(
        $table, $column, $keyList, $row, $columnTsConfig
    );
    $translatedLabels = implode(
        ', ',
        array_map($languageService->sL(...), $labels)
    );

.. index:: PHP-API, FullyScanned, ext:backend
