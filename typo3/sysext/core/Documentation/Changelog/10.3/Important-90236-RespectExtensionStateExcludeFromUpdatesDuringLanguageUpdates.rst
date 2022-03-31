.. include:: /Includes.rst.txt

========================================================================================
Important: #90236 - Respect extension state 'excludeFromUpdates' during language updates
========================================================================================

See :issue:`90236`

Description
===========

If the state property inside :file:`ext_emconf.php` is set to `excludeFromUpdates`,
the extension will be skipped while updating the language files in the Install Tool.

This setting is especially helpful if you create a custom extension which uses the same extension
key as an existing TER extension.

.. index:: Backend, ext:core
