
.. include:: ../../Includes.txt

=============================================================
Feature: #72505 - Introduce hook to override a record overlay
=============================================================

See :issue:`72505`

Description
===========

Prior to TYPO3 7 LTS, it was possible to override a record overlay in Web > List.
This patch introduces a new hook with the old functionality.

The hook is called with the following signature:

.. code-block:: php

   /**
    * @param string $table
    * @param array $row
    * @param array $status
    * @param string $iconName
    * @return string the new (or given) $iconName
    */
   function postOverlayPriorityLookup($table, array $row, array $status, $iconName)

Register the hook
-----------------

Register the hook class which implements the method with the name `postOverlayPriorityLookup`:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][IconFactory::class]['overrideIconOverlay'][] = \VENDOR\MyExt\Hooks\IconFactoryHook::class;
