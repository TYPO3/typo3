.. include:: /Includes.rst.txt

.. _adjust-template-of-widget:

==========================
Adjust template of widgets
==========================

When adding own widgets, it might be necessary to provide custom templates.
In such a case the file path containing the template files needs to be added.
Thats done in the same way as for other Extbase backend modules:

.. code-block:: typoscript

   module.tx_dashboard {
       view {
           templateRootPaths {
               110 = EXT:extension_key/Resources/Private/Templates/Dashboard/Widgets/
           }
       }
   }

The location of that snippet depends on your setup and preferences.
Add it in database "Setup" ``config`` field of an "Template" ``sys_template`` record.
Or follow one of the other file based ways. See :ref:`t3sitepackage:typoscript-configuration`.

.. note::

   Keys ``0 - 100`` are reserved for TYPO3 system extension.
