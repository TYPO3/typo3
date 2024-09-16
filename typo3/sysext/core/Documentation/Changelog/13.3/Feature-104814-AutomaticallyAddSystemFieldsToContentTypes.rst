.. include:: /Includes.rst.txt

.. _feature-104814-1725444916:

===================================================================
Feature: #104814 - Automatically add system fields to content types
===================================================================

See :issue:`104814`

Description
===========

All content elements types (:php:`CType`) are usually equipped with the same
system fields (`language`, `hidden`, etc.) - see also :ref:`feature-104311-1720176189`.
Adding them to the editor form has previously been done by adding those fields
to each content types' :php:`showitem` definition.

In the effort to simplify content element creation, to unify the available
fields and position for the editor and to finally reduce configuration effort
for integrators, those system fields are now added automatically based
on the :php:`ctrl` definition.

.. note::

    The fields are added to the :php:`showitem` through their corresponding
    palettes. In case such palette has been changed by extensions, the required
    system fields are added individually to corresponding tabs.

The following tabs / palettes are now added automatically:

* The :guilabel:`General` tab with the `general` palette at the very beginning
* The :guilabel:`Language` tab with the `language` palette after custom fields
* The :guilabel:`Access` tab with the `hidden` and `access` palettes
* The :guilabel:`Notes` tab with the `rowDescription` field

As mentioned, in case one of those palettes has been changed to no longer
include the corresponding system fields, those fields are added individually
depending on their definition in the table's :php:`ctrl` section:

* The :php:`ctrl[type]` field (usually :php:`CType`)
* The :php:`colPos` field
* The :php:`ctrl[languageField]` (usually :php:`sys_language_uid`)
* The :php:`ctrl[editlock]` field (usually :php:`editlock`)
* The :php:`ctrl[enablecolumns][disabled]` field (usually :php:`hidden`)
* The :php:`ctrl[enablecolumns][starttime]` field (usually :php:`starttime`)
* The :php:`ctrl[enablecolumns][endtime]` field (usually :php:`endtime`)
* The :php:`ctrl[enablecolumns][fe_group]` field (usually :php:`fe_group`)
* The :php:`ctrl[descriptionColumn]` field (usually :php:`rowDescription`)

By default, all custom fields - the ones still defined in :php:`showitem` - are
added after the `general` palette and are therefore added to the
:guilabel:`General` tab, unless a custom tab (e.g. :guilabel:`Plugin`,
or :guilabel:`Categories`) is defined in between. It is also possible to start
with a custom tab by defining a `--div--` as the first item in the
:php:`showitem`. In this case, the :guilabel:`General` tab will be omitted.

All those system fields, which are added based on the :php:`ctrl` section are
also automatically removed from any custom palette and from the customized
type's :php:`showitem` definition.

If the content element defines the :guilabel:`Extended` tab, it will be
inserted at the end, including all fields added to the type via API methods,
without specifying a position, e.g. via
:php:`ExtensionManagementUtility::addToAllTcaTypes()`.

Impact
======

Creating content elements has been simplified by removing the need to
define the system fields for each element again and again. This shrinks
down a content element's :php:`showitem` to just the element specific fields.

A usual migration will therefore look like the following:

Before:

.. code-block:: php

    'slider' => [
        'showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                --palette--;;general,
                --palette--;;headers,
                slider_elements,
                bodytext;LLL:EXT:awesome_slider/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.slider_description,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                --palette--;;frames,
                --palette--;;appearanceLinks,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                --palette--;;language,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                --palette--;;hidden,
                --palette--;;access,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                categories,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                rowDescription,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        ',
    ],

After:

.. code-block:: php

    'slider' => [
        'showitem' => '
                --palette--;;headers,
                slider_elements,
                bodytext;LLL:EXT:awesome_slider/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.slider_description,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                categories,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        ',
    ],

Since all fields, palettes and tabs, which are defined in the :php:`showitem`
are added after the :php:`general` palette, also the :guilabel:`Categories` tab
- if defined - is displayed before the system tabs / fields. The only special
case is the :guilabel:`Extended` tab, which is always added at the end.

.. important::

    For consistency reasons, custom labels for system fields are no
    longer preserved.

.. index:: PHP-API, TCA, ext:core
