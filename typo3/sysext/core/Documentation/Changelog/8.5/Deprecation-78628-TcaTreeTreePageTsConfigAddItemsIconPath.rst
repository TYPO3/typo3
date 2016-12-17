.. include:: ../../Includes.txt

==============================================================
Deprecation: #78628 - TCA tree pageTsConfig addItems icon path
==============================================================

See :issue:`78628`

Description
===========

When adding items to `TCA` `type="select"` fields with `pageTSConfig`, the syntax for icons has been changed:

Example to add an item with icon in pages to field category before:

.. code-block:: typoscript

    # Add an item with text "staticFromPageTs" to field category in pages
    TCEFORM.pages.category.addItems.12345 = staticFromPageTs
    # Assign icon to the element
    TCEFORM.pages.category.addItems.12345.icon = EXT:any_extension/Resources/Public/Icons/Smiley.png

The path has been deprecated and now accepts icon identifiers from the icon registry only:

.. code-block:: typoscript

    # Add an item with text "staticFromPageTs" to field category in pages
    TCEFORM.pages.category.addItems.12345 = staticFromPageTs
    # Assign icon to the element
    TCEFORM.pages.category.addItems.12345.icon = my-registered-icon


Impact
======

Using a file path syntax will trigger a deprecation log entry, but will work until TYPO3 v9.


Affected Installations
======================

Instances that use this PageTSConfig setting with a file path instead of an icon identifier.


Migration
=========

Register the icon within the :php:`IconRegistry` and use an icon identifier instead of the file path.

.. index:: TSConfig, Backend, TCA
