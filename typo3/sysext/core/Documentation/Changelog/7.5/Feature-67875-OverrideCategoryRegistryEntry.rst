
.. include:: ../../Includes.txt

===================================================
Feature: #67875 - Override CategoryRegistry entries
===================================================

See :issue:`67875`

Description
===========

The `makeCategorizable` method of the  `\TYPO3\CMS\Core\Utility\ExtensionManagementUtility`
class has been extended with a new parameter `override` to set a new category configuration for
an already registered table / field combination.

If the parameter is set to `TRUE`, previously defined registry entries are cleared for the
current table / field combination before adding the new configuration.

The intended usecase for this method is to add additional TCA types for a previously registered table.

A good example is the `tt_content` table:

1. The basic TCA is defined in the `frontend` Extension.
2. After the processing of the normal TCA definition, the default categorized tables (from the install
   tool setting `SYS/defaultCategorizedTables`) are initialized and the categories tab is added to the
   `showitem` configuration for all TCA types that exist so far.
3. Now the TCA overrides are processed. The `css_styled_content` Extension defines additional TCA
   types. After the types are defined the `addOrOverride()` method is called to add the category
   tab to them.


Impact
======

The current behavior of the existing functionality is not changed. Only new functionality is added.


Example
=======

.. code-block:: php

	// This example is from the tt_content TCA overrides file from the css_styled_content Extension.
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable('css_styled_content', 'tt_content', 'categories', array(), TRUE);


.. index:: PHP-API, TCA, Backend
