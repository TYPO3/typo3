.. include:: /Includes.rst.txt

.. _changelog-Feature-91008-ItemGroupingForTCASelectItems:

====================================================
Feature: #91008 - Item grouping for TCA select items
====================================================

See :issue:`91008`

Description
===========

The TCA column type ``select`` now has a clean API to group items for dropdowns
in FormEngine. This was previously handled via placeholder ``--div--`` items,
which then rendered as :html:`<optgroup>` HTML elements in a dropdown.

In larger installations or TYPO3 instances with lots of extensions, Plugins
(:php:`tt_content.list_type`), Content Types (:php:`tt_content.CType`) or custom
Page Types (:php:`pages.doktype`) drop down lists could grow large and adding item groups
caused tedious work for developers or integrators.
Grouping can now be configured on a per-item
basis. Custom groups can be added via an API or when defining TCA for a new table.

Adding Custom Select Item Groups
--------------------------------

Registration of a select item group takes place in :file:`Configuration/TCA/tx_mytable.php`
for new TCA tables, and in :file:`Configuration/TCA/Overrides/a_random_core_table.php`
for modifying an existing TCA definition.

The following two examples illustrate adding a new group to a field of
type "select":

.. code-block:: php

   ExtensionManagementUtility::addTcaSelectItemGroup(
       'tt_content',
       'CType',
       'sliders',
       'LLL:EXT:my_slider_mixtape/Resources/Private/Language/locallang_tca.xlf:tt_content.group.sliders',
       'after:lists'
   );

The TCA for :php:`tt_content.CType` column configuration looks like this now:

.. code-block:: php

   'items' => ...
   'itemGroups' => [
       'default' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.standard',
       'lists' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.lists',
       'sliders' => 'LLL:EXT:my_slider_mixtape/Resources/Private/Language/locallang_tca.xlf:tt_content.group.sliders',
       'menu' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.menu',
       'forms' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.forms',
       'special' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.special',
    ],

When adding a new select field, itemGroups should be added directly in the
original TCA definition without using the API method. Use the API within
:file:`TCA/Configuration/Overrides/` files to extend an existing TCA select field with
grouping.

Attaching Select Items to Item Groups
-------------------------------------

A select item now has a fourth array key to define a "Group ID" which group it
belongs to. In the example above, the group ID is named "sliders" and used
in the examples below to attach items to this group.

Grouping for select items can be used via API or in TCA configuration directly.

This is the example for a custom Content Type "slickslider" belonging to the
group from above:

.. code-block:: php

   'items' => [
       ...,
       [
           // Label
           'LLL:EXT:my_slider_mixtape/Resources/Private/Locallang/locallang_tca.xlf:tt_content.CType.slickslider',
           // Value written to the database
           'slickslider',
           // Icon for the dropdown
           'EXT:my_slider_mixtape/Resources/Public/Icons/slickslider.png',
           // The group ID, if not given, falls back to "none" or the last used --div-- in the item array
           'sliders'
       ],
   ]


The item can be added via API like this:

.. code-block:: php

   ExtensionManagementUtility::addTcaSelectItem(
       'tt_content',
       'CType',
       [
           'LLL:EXT:my_slider_mixtape/Resources/Private/Locallang/locallang_tca.xlf:tt_content.CType.slickslider',
           'slickslider',
           'EXT:my_slider_mixtape/Resources/Public/Icons/slickslider.png',
           'sliders'
       ]
   );

The same approach applies to :php:`ExtensionManagementUtility::addPlugin()` when
adding pi-based plugins.

When adding Extbase plugins, the API method now allows to specify a group ID
directly as additional parameter. This falls back to the "default" group ID,
which is available in :php:`tt_content.CType` and :php:`tt_content.list_type`.

.. code-block:: php

   ExtensionUtility::registerPlugin(
       // Extension key
       'my_slider_mixtape',
       // Plugin value
       'slider_from_records',
       // Plugin label
       'LLL:EXT:my_slider_mixtape/Resources/Private/Locallang/locallang_tca.xlf:tt_content.plugin.slider_from_records',
       // Icon for plugin
       'EXT:my_slider_mixtape/Resources/Public/Icons/slickslider.png',
       // Group ID
       'sliders'
   );


Impact
======

By default, Page Types (:php:`pages.doktype`), Content Types (:php:`tt_content.CType`) and
Plugins (:php:`tt_content.list_type`) now have native grouping enabled.

The order of the :php:`itemGroups` value is important when using groups, as this
is the order of the groups rendered in the dropdown of FormEngine.

The API methods can be used to build more groups without juggling with
TCA arrays.

It is possible now, and encouraged to remove the :php:`--div--` items in custom
selects and use itemGroups instead. TYPO3 Core keeps the :php:`--div--` for
backwards-compatible reasons in TYPO3 v10, but all items of the fields mentioned
above the grouping parameter has been added already.

Please note that this :php:`--div--` is related to select items, and not the
"showItem" definition which fields should be shown.

Currently Item Groups are used in FormEngine DropDowns / single-select items
from TYPO3 Core, but can be used in multi-select fields as well.

.. index:: TCA, ext:core
