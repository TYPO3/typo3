.. include:: ../../Includes.txt

=======================================================================================
Feature: #82488 - Possibility to modify the display results before FluidView assignment
=======================================================================================

See :issue:`82488`

Description
===========

To manipulate the data of the search results prior to rendering them in the frontend a
hook has been introduced at the end of the :php:`getDisplayResults()` method, called
:php:`getDisplayResults_postProc`.
The hook can modify all data just before it is passed to fluid.

Basic Usage
===========

Registration of the new hook in :file:`ext_localconf.php` of your custom extension.

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['pi1_hooks']['getDisplayResults_postProc'] = \Vendor\ExtensionName\Hooks\CustomHook::class;

CustomHook class example :php:`\Vendor\ExtensionName\Hooks\CustomHook`

.. code-block:: php

   <?php
   declare(strict_types = 1);
   namespace Vendor\ExtensionName\Hooks;

   class CustomHook
   {
      /**
      * @param array $result
      * @return array
      */
       public function getDisplayResults_postProc(array $result): array
       {
           if ($result['count'] > 0) {
               foreach($result['rows'] as $rowIndex => $row) {
                   $result['rows'][$rowIndex]['description'] = \str_replace('foo', 'bar', $row['description']);
               }
           }
           return $result;
       }
   }

.. index:: ext:indexed_search, PHP-API, Fluid
