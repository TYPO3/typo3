.. include:: ../../Includes.txt

==============================================================
Important: #79647 - Added Hook for resolving custom link types
==============================================================

See :issue:`79647`

Description
===========

A newly introduced hook in :php:`LinkService->resolveByStringRepresentation` allows to resolve custom link types with
special syntax. A reference to the empty :php:`$result` array is passed as well as the :php:`$urn` string that could not be
resolved by the core.

Example
=======

An example implementation for custom links that use `myLinkIdentifier:` as a prefix could look like this:

:file:`EXT:my_site/ext_localconf.php`

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Link']['resolveByStringRepresentation'][] =
      \MyVendor\MySite\Hooks\LinkServiceHook::class . '->resolveByStringRepresentation';


:file:`EXT:my_site/Classes/Hooks/LinkServiceHook.php`

.. code-block:: php

   namespace MyVendor\MySite\Hooks;

   class LinkServiceHook
   {
      public function resolveByStringRepresentation(array $parameters): void
      {
         // Only care for links that start with myLinkIdentifier:
         if (stripos($parameters['urn'], 'myLinkIdentifier:') !== 0) {
            return;
         }

         $parameters['result'] = ['myLinkIdentifier' => substr($parameters['urn'], 17)]
         $parameters['result']['type'] = 'myLinkIdentifier';
      }
   }

.. index:: Backend, PHP-API
