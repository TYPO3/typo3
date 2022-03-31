.. include:: /Includes.rst.txt

====================================================================
Important: #94280 - Move contents of ext_*.php into global namespace
====================================================================

See :issue:`94280`

Description
===========

When warming up caches, the code of the files :file:`ext_localconf.php` and
:file:`ext_tables.php` are now scoped into the global namespace.

.. warning::

   The content of such :file:`ext_*.php` files **must not** be wrapped in a
   local namespace by extension authors. This will result in nested namespaces
   and therefore cause PHP errors only solvable by clearing the caches via
   Install Tool!


Example code from the cache file:

.. code-block:: php

   /**
    * Extension: frontend
    * File: /var/www/html/public/typo3/sysext/frontend/ext_localconf.php
    */

   namespace {
       // Content of EXT:frontend/ext_localconf.php
   }

Having a namespace allows extension authors to import classes by the
keyword :php:`use`.

Example :file:`ext_localconf.php`:

.. code-block:: php

   <?php

   use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

   defined('TYPO3') or die();

   ExtensionManagementUtility::addUserTSConfig('
       options.saveDocView = 1
       options.saveDocNew = 1
       options.saveDocNew.pages = 0
       options.saveDocNew.sys_file = 0
       options.saveDocNew.sys_file_metadata = 0
       options.disableDelete.sys_file = 1
   ');


.. index:: PHP-API, ext:core
