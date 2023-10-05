.. include:: /Includes.rst.txt

.. _breaking-96874-1664488429:

=============================================================
Breaking: #96874 - CKEditor-related plugins and configuration
=============================================================

See :issue:`96874`

Description
===========

TYPO3 v12 ships with CKEditor 5, which is a completely new, rewritten editor
compared to CKEditor 4 which was shipped since TYPO3 v8.

Any kind of plugin, which was written for CKEditor4 is not compatible anymore.

In addition, since CKEditor 5 does not grant HTML input the same way as before.

Impact
======

Any plugin in CKEditor will not be loaded anymore in TYPO3 v12.

It might be possible to have data loss when editing and saving data,
especially since some configuration formats have been changed.

Affected installations
======================

TYPO3 installations with custom extensions extending CKEditor with plugins,
or relying on a specific logic to save data with specific contents
(such as additional allowed HTML tags).

Migration
=========

In general, it is advised to read the `CKEditor 4 to 5 migration <https://ckeditor.com/docs/ckeditor5/latest/installation/getting-started/migration-from-ckeditor-4.html#plugins>`__
to understand the conceptual changes, also related to plugins.

Writing a custom plugin for CKEditor 5 can be done in TypeScript or JavaScript,
using the `CKEditor5 plugin system <https://ckeditor.com/docs/ckeditor5/latest/installation/advanced/plugins.html>`__.

Example - A timestamp plugin :js:`@my-vendor/my-package/timestamp-plugin.js`
which adds a toolbar item to add the current timestamp into the editor.

..  code-block:: javascript

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

In the RTE configuration, this then needs to be added like this:

.. code-block:: yaml

  editor:
    config:
      importModules:
        - { module: '@my-vendor/my-package/timestamp-plugin.js', exports: ['Timestamp'] }
      toolbar:
        items:
          - bold
          - italic
          - '|'
          - clipboard
          - undo
          - redo
          - '|'
          - timestamp

.. index:: RTE, NotScanned, ext:rte_ckeditor
