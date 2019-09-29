.. include:: ../Includes.txt


.. _config-typo3:

==========================
TYPO3 Configuration Basics
==========================

Just in case you are not familiar with how to configure TYPO3, we will
give you a very brief introduction. In case you are , you can safely
skip this part and continue reading
:ref:`config-concepts`.

We only cover configuration methods that are used to configure `rte_ckeditor`.


.. _config-typo3-page-tsconfig:

Page TSconfig
=============

Relevant Settings for `rte_ckeditor`
------------------------------------

Page TSconfig can be used to change

* the preset used in general
* or the preset used by specific database table fields.
* or the preset used by specific database table fields for a specific table type (see :ref:`t3tca:types`).

.. code-block:: typoscript

   # Default preset
   RTE.default.preset = full

   # RTE.config.table.column.preset = presetIdentifier
   RTE.config.tx_news_domain_model_news.preset = minimal

   # RTE.config.table.column.types.name.preset = presetIdentifier
   RTE.config.tt_content.types.textpic.preset = default

For more examples, see :ref:`t3tsconfig:pageTsRte` in "TSconfig Reference".

How to change values
--------------------

As Page TSconfig always applies to a page and its subpages, you can modify it by
editing a page.

#. Go to the :guilabel:`"WEB" > "Page"` module.
#. Select a page in the page tree (usually
   your root page).
#. Click on the button to :guilabel:`Edit page properties`

   .. figure:: images/edit_page_properties.png
      :class: with-shadow

#. Select the :guilabel:`"Resources"` tab
#. Enter the Page TSconfig in the field :guilabel:`"Page TSconfig"`


Additionally, you can add Page TSconfig in an extension: :file:`Configuration/TSconfig/Page`, see
:ref:`best-practice-sitepackage`.

How to view settings
--------------------

Go to the module :guilabel:`"WEB" > "Info"` and select :guilabel:`"Page TSconfig"`.




.. _config-typo3-global-configuration:

Global Configuration
====================

Global Configuration is system wide general configuration.

Relevant Settings for `rte_ckeditor`
------------------------------------

The setting :php:`$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']` is used to configure
the available presets for rich text editing.

By default, the presets "minimal", "default" and "full" are defined.

If you add a new preset, you must add it to this array.


How to change values
--------------------

Usually, Global Configuration can be configured in the backend in
:guilabel:`"ADMIN TOOLS" > "Settings" > "Configure Installation-Wide Options"`.

However, the settings relevant for rich text editing, :php:`$GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']`
cannot be configured in the backend.

You must either configure this in:

#. The file :file:`typo3conf/AdditionalConfiguration.php`
#. Or in an extension in the file :file:`EXT:<extkey>/ext_localconf.php`

.. code-block:: php

   if (empty($GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['myCustomPreset'])) {
       $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['myCustomPreset'] = 'EXT:<extkey>/Configuration/RTE/MyCustomPreset.yaml';
   }


How to view settings
--------------------

You can view the Global Configuration in
:guilabel:`"SYSTEM" > "Configuration" > "$GLOBAL['TYPO3_CONF_VARS'] (Global Configuration)" > "RTE"`.

.. figure:: images/global-configuration-rte.png
   :class: with-shadow

   Global Configuration: RTE > Presets



.. _config-typo3-yaml:

Yaml
====

Most of the configuration of `rte_ckeditor` will be done in a Yaml file.


Relevant Settings for `rte_ckeditor`
------------------------------------

See :ref:`config-ref`

How to change values
--------------------

This is done directly in the file. The Yaml file should be included in a
sitepackage extension, see :ref:`best-practice-sitepackage`.


.. _config-typo3-tca:

TCA
===

The :abbr:`table configuration array (TCA)` is used to configure database fields and how they will behave in the
backend when edited. It is for example used to define that tt_content.bodytext should be edited
with a rich text editor.


Relevant Settings for `rte_ckeditor`
------------------------------------

* :ref:`t3tca:columns-text-properties-enableRichtext`
* :ref:`t3tca:columns-text-properties-richtextConfiguration`

How to change values
--------------------

This must be done in an extension in :file:`Configuration/TCA`. Usually this is done within a custom sitepackage
extension, see :ref:`best-practice-sitepackage`.

How to view settings
--------------------

You can view TCA in the backend:
:guilabel:`"SYSTEM" > "Configuration" > "$GLOBAL['TCA'] (Table configuration array)"`.

For example, look at :guilabel:`tt_content > columns > bodytext`.

However, you will
find that neither `enableRichtext`, nor `richtextConfiguration` is set here. They
are configured in :guilabel:`tt_content > types` for various content types, for example
look at :guilabel:`tt_content > types > text > columnOverrides`.

.. figure:: images/column_overrides.png
   :class: with-shadow

   TCA: tt_content > types > text > columnsOverride > bodytext



