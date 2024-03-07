.. include:: /Includes.rst.txt

.. _config-examples:

======================
Configuration Examples
======================

How do I use a different preset?
================================

Instead of using the default "default" preset, you can change this, for example
to "full", using **page TSconfig**:

.. code-block:: typoscript
   :caption: EXT:my_sitepackage/Configuration/page.tsconfig

   RTE.default.preset = full

Of course, the preset must already exist, or you must define it. `rte_ckeditor`
ships with presets "minimal", "default" and "full".

Additionally, you can set specific presets for specific types of textfields.

For example to use preset "full" for the field "bodytext" of all content elements:

.. code-block:: typoscript
   :caption: EXT:my_sitepackage/Configuration/page.tsconfig

   RTE.config.tt_content.bodytext.preset = full

To use preset "minimal" for the field "bodytext" of only content elements
with ctype="text":

.. code-block:: typoscript
   :caption: EXT:my_sitepackage/Configuration/page.tsconfig

   RTE.config.tt_content.bodytext.types.text.preset = minimal

For more examples, see :ref:`t3tsconfig:pageTsRte` in "TSconfig Reference".


How do I create my own preset?
==============================

In your sitepackage extension:

In :file:`ext_localconf.php`, replace `my_extension` with your extension key, replace `my_preset` and `MyPreset.yaml`
with the name of your preset.

.. code-block:: php
   :caption: EXT:my_sitepackage/ext_localconf.php

   $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']['my_preset']
      = 'EXT:my_extension/Configuration/RTE/MyPreset.yaml';

In :file:`Configuration/RTE/MyPreset.yaml`, create your configuration, for example:

.. literalinclude:: _Examples/_MyPreset.yaml
   :language: yaml
   :caption: EXT:my_sitepackage/Configuration/RTE/MyPreset.yaml

See also the note for :option:`editor.config.contentsCss`.

How do I customize the toolbar?
===============================

The toolbar can be customized individually by configuring required toolbar
items in the YAML configuration. The following configuration shows the toolbar
configuration of the minimal editor setup included in file
:file:`EXT:rte_ckeditor/Configuration/RTE/Minimal.yaml`:

.. literalinclude:: _Examples/_CustomizeToolbar.yaml
   :language: yaml
   :caption: EXT:my_sitepackage/Configuration/RTE/MyPreset.yaml

The :yaml:`'|'` can be used as a separator between groups of toolbar items.

Additional configuration options are available in the official CKEditor 5
`Toolbar documentation <https://ckeditor.com/docs/ckeditor5/latest/features/toolbar/toolbar.html>`__

.. _config-example-toolbargrouping:

Grouping toolbar items in drop-downs
------------------------------------

To save space in the toolbar or to arrange the features thematically, it is
possible to group several items into a dropdown as shown in the following
example:

.. literalinclude:: _Examples/_GroupingToolbarItems.yaml
   :language: yaml
   :caption: EXT:my_sitepackage/Configuration/RTE/MyPreset.yaml


How do I allow a specific tag?
==============================

Allowed content in CKEditor5 is to be configured via the General HTML Support
plugin option :yaml:`config.htmlSupport`.

.. literalinclude:: _Examples/_AllowSpecificTag.yaml
   :language: yaml
   :caption: EXT:my_sitepackage/Configuration/RTE/MyPreset.yaml

.. note::
   :yaml:`config.htmlSupport` only applies to elements that are "known" to
   CKEditor5. Tags like :html:`<svg>` or custom elements like
   :html:`<my-element>` are not configurable this way as
   :yaml:`htmlSupport.allow` can only handle
   elements that are defined in the `CKEditor5 schema`_.

.. _CKEditor5 schema: https://ckeditor.com/docs/ckeditor5/latest/features/html/general-html-support.html#enabling-custom-elements


.. _config-example-fontplugin:

How do I configure the font plugin?
===================================

.. versionadded:: 12.4.12

In order to use the font plugin, the RTE configuration needs to be adapted:

.. literalinclude:: _Examples/_FontPlugin.yaml
   :language: yaml
   :caption: EXT:my_sitepackage/Configuration/RTE/MyPreset.yaml

More information can be found in the
`official documentation of CKEditor <https://ckeditor.com/docs/ckeditor5/latest/features/font.html>`__.

.. _config-example-customplugin:

How do I create a custom plugin?
================================

With CKEditor 5 the plugin architecture has changed and CKEditor 4 plugins
are not compatible with CKEditor 5. It is advised to read the
`CKEditor 4 to 5 migration <https://ckeditor.com/docs/ckeditor5/latest/installation/getting-started/migration-from-ckeditor-4.html#plugins>`__
to understand the conceptual changes, also related to plugins.

Writing a custom plugin for CKEditor 5 can be done in TypeScript or JavaScript,
using the `CKEditor5 plugin system <https://ckeditor.com/docs/ckeditor5/latest/installation/advanced/plugins.html>`__.

In this example, we integrate a simple timestamp plugin to CKEditor 5.
Make sure to replace `<my_extension>` with your extension key.

.. rst-class:: bignums

1. Create the plugin file

   Add the following ES6 JavaScript code:

   .. code-block:: javascript
      :caption: EXT:<my_extension>/Resources/Public/JavaScript/Ckeditor/timestamp-plugin.js

      import { Plugin } from '@ckeditor/ckeditor5-core';
      import { ButtonView } from '@ckeditor/ckeditor5-ui';

      export class Timestamp extends Plugin {
        static pluginName = 'Timestamp';

        init() {
          const editor = this.editor;

          // The button must be registered among the UI components of the editor
          // to be displayed in the toolbar.
          editor.ui.componentFactory.add(Timestamp.pluginName, () => {
            // The button will be an instance of ButtonView.
            const button = new ButtonView();

            button.set({
              label: 'Timestamp',
              withText: true
            });

            // Execute a callback function when the button is clicked
            button.on('execute', () => {
              const now = new Date();

              // Change the model using the model writer
              editor.model.change(writer => {

                // Insert the text at the user's current position
                editor.model.insertContent(writer.createText(now.toString()));
              });
            });

            return button;
          });
        }
      }

2. Register the ES6 JavaScript

   .. literalinclude:: _Examples/_timestamp-plugin_JavaScriptModules.php
      :language: php
      :caption: EXT:<my_extension>/Configuration/JavaScriptModules.php

3. Include the plugin in the CKEditor configuration

   .. literalinclude:: _Examples/_timestamp-plugin.yaml
      :language: yaml
      :caption: EXT:<my_extension>/Configuration/RTE/MyPreset.yaml
      :emphasize-lines: 4,14
      :linenos:

   The :yaml:`importModules` item in line 4 imports the previously registered ES6
   module. The :yaml:`timestamp` item in line 14 adds the plugin to the toolbar.

4. Use the plugin

   .. figure:: images/timestamp-plugin.png
      :class: with-shadow
      :alt: The custom timestamp plugin in the editor

      The custom timestamp plugin in the editor

.. -------------------------------------
.. todo: additional questions
   What are stylesets?
   Some configuration can be done with Page TSconfig, some with TCA and some with YAML and some with either 2 or more of these. Why and what should be configured where?
   How can I configure classes to anchor tags?
   What is the contents.css?
   How can I set specific classes for anchors?
   How can I extend custom tags?
   How can I add images?
   How can I configure tables?
   How can I add more attributes to anchor tags?
   How can I allow / deny specific tags?
   How to add custom styles for ul tags?
