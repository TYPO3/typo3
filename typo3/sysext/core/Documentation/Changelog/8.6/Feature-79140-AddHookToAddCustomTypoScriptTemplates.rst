.. include:: /Includes.rst.txt

=============================================================
Feature: #79140 - Add hook to add custom TypoScript templates
=============================================================

See :issue:`79140`

Description
===========

A new hook in TemplateService allows to add or modify existing TypoScript templates.

Register the hook via :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Core/TypoScript/TemplateService']['runThroughTemplatesPostProcessing']`
in the extensions' ext_localconf.php file.


Example
=======

An example implementation could look like this:

EXT:my_site/ext_localconf.php

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Core/TypoScript/TemplateService']['runThroughTemplatesPostProcessing'][1313131313] =
      \MyVendor\MySite\Hooks\TypoScriptHook::class . '->addCustomTypoScriptTemplate';


EXT:my_site/Classes/Hooks/TypoScriptHook.php

.. code-block:: php

   namespace MyVendor\MySite\Hooks;

   class TypoScriptHook
   {

      /**
       * Hooks into TemplateService after
       * @param array $parameters
       * @param \TYPO3\CMS\Core\TypoScript\TemplateService $parentObject
       * @return void
       */
      public function addCustomTypoScriptTemplate($parameters, $parentObject)
      {
         // Disable the inclusion of default TypoScript set via TYPO3_CONF_VARS
         $parameters['isDefaultTypoScriptAdded'] = true;
         // Disable the inclusion of ext_typoscript_setup.txt of all extensions
         $parameters['processExtensionStatics'] = false;

         // No template was found in rootline so far, so a custom "fake" sys_template record is added
         if ($parentObject->outermostRootlineIndexWithTemplate === 0) {
            $row = [
               'uid' => 'my_site_template',
               'config' => '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:my_site/Configuration/TypoScript/site_setup.t3s">',
               'root' => 1,
               'pid' => 0
            ];
            $parentObject->processTemplate($row, 'sys_' . $row['uid'], 0, 'sys_' . $row['uid']);
         }
      }
   }

.. index:: PHP-API, TypoScript, Frontend
