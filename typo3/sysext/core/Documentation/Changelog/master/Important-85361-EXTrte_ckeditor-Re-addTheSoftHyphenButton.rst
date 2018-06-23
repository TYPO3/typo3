.. include:: ../../Includes.txt

====================================================================
Important: #85361 - EXT:rte_ckeditor - re-add the soft hyphen button
====================================================================

See :issue:`85361`

Description
===========

With the switch from htmlArea to CKEditor the soft hyphen button was gone. This functionality is now
re-added as custom CKEditor plugin.

It is loaded like each other existing CKEditor plugin in the TYPO3 core via
:file:`EXT:rte_ckeditor/Configuration/RTE/Editor/Plugins.yaml`. It's automatically loaded in
the RTE presets "default" and "full". The shortcut `Ctrl` + `-` for adding a soft hyphen works
without showing the button in the CKEditor button bar.

Impact
======

By using the shipped RTE presets "default" or "full", the functionality and the toolbar button is
automatically added to CKEditor toolbar. This helps the editor immensely to create better content for
the responsive web these days.

How to activate the functionality in a custom RTE preset
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The functionality is automatically added if you are importing
:file:`EXT:rte_ckeditor/Configuration/RTE/Editor/Plugins.yaml` in your custom RTE preset. If your
custom RTE preset can't rely on that file, you can explicitly import this plugin as shown:

.. code-block::

   editor:
     externalPlugins:
       softhyphen:
         resource: "EXT:rte_ckeditor/Resources/Public/JavaScript/Plugins/softhyphen/"
         # set whether the shortcut for this plugin is activated or not
         enableShortcut: true

How to add the button in a custom RTE preset
''''''''''''''''''''''''''''''''''''''''''''

The button with the buttonName `softHyphen` of the plugin is assigned to a toolbarGroup named
`insertcharacters`. Based on how you like to configure the toolbar in your RTE preset you must either
use the toolbarGroup or the buttonName to display the button at the desired location in the toolbar.

Please take look into the supplied RTE presets to see working examples:
- :file:`EXT:rte_ckeditor/Configuration/RTE/Default.yaml`
- :file:`EXT:rte_ckeditor/Configuration/RTE/Full.yaml`

More information can be found in the official CKEditor 4 documentation (toolbar concepts):
- https://docs.ckeditor.com/ckeditor4/latest/guide/dev_toolbarconcepts.html

.. index:: RTE, ext:rte_ckeditor
