.. include:: /Includes.rst.txt

================================================================================
Important: #78383 - TCA: Streamline field positions in tabs for recurring fields
================================================================================

See :issue:`78383`

Description
===========

In TYPO3 there are some recurring field definitions shared by a lot of records.
These fields are mostly defined in :php:`$GLOBALS['TCA']['<mytable>']['ctrl']`.
Furthermore the generic categories are taken into account.

These fields are used by core records and third party extensions.

These fields should have a generic position in the edit form (`EditDocumentController` / `FormEngine`) to allow the
editor or integrator to have a valid guess where to look for a common option. Furthermore the fields should be placed
in the given order in the certain tab.

There should be no records not using tabs to group fields.

See the documentation for the definition of the recurring fields:

* ctrl_
* categories_

.. _ctrl: https://docs.typo3.org/typo3cms/TCAReference/Reference/Ctrl/Index.html
.. _categories: https://docs.typo3.org/typo3cms/CoreApiReference/ApiOverview/Categories/Index.html


**Legend**

For the fields name in "Generic fields" the actual value of
:php:`$GLOBALS['TCA']['<mytable>']['ctrl']['<generic field>']` should be set in
:php:`$GLOBALS['TCA']['<mytable>']['types'][<mytype>]['showitem']`.


General (first tab)
-------------------

Label:
    `LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general`
Generic fields:
    * `type` (if there is a field that is not set as type in ctrl, but has a similar meaning, set it to the first
      position)
    * `label`
    * `label_alt` (if the fields are directly related to the label; especially if `label_alt_force` is set `true`)
Additional fields:
    Fields that reflect the main focus of an editor or integrator working with the record.

Following tabs
--------------

The following tabs should be defined by the specific record. They should have speaking names. Avoid unspecific
labelling (for example options, settings, extended, miscellaneous) as those labels do not guide the editor.

Language
--------

Label:
    `LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language`
Generic fields:
    * `languageField`
    * `transOrigPointerField`
Additional fields:
    Other fields that affects the language or translation handling.

Access
------
Label:
    `LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access`
Generic fields:
    * `enablecolumns`
    * `disabled`
    * `starttime` (Use a palette for starttime and endtime)
    * `endtime`
    * `fe_group`
    * `fe_admin_lock`
    * `editlock`
Additional fields:
    Other fields that affects the access handling in FE or BE.

Categories
----------

Label:
    `LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories`
Generic fields:
    Field that is defined by :php:`\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable`
    It is not recommended to use the configuration option `defaultCategorizedTables` to make a table categorizable as
    the tab position might not be consistent.
Additional fields:
    Other fields that are category related (e.g. select main category)

Notes
-----

Label:
    `LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes`
Generic fields:
    `descriptionColumn`
Additional fields:
    Other fields for internal remarks of editors or integrators.
    These fields should not affect the website frontend.

Extended
--------

Label:
    `LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended`
Generic fields:
    No.
Additional fields:
    No.
    There should be no additional field in this tab as the labelling is too generic to provide a good UX.
    It should be only added to prevent that accidentally added fields from third party extensions are placed in last
    tab.

.. index:: TCA, Backend, LocalConfiguration
