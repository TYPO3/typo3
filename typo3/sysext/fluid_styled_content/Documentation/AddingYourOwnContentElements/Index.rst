.. include:: ../Includes.txt

.. _adding-your-own-content-elements:

================================
Adding your own content elements
================================

.. note::

   This part is written for developers!

A content element can be based on already available fields in the `tt_content` table and/or extra fields you can add to the `tt_content` table.
This is done the same way as you do for your own extensions by :ref:`extending TCA <t3coreapi:extending>`.
Depending on the data in the `tt_content` table, you can send the data immediately to the :ref:`cobj-fluidtemplate`
or use a :ref:`data processor <t3tsref:cobj-fluidtemplate-properties-dataprocessing>` in front to do some data manipulation.
The content elements in the extension "fluid_styled_content" are using both as well.
A data processor is sometimes used to convert a string (like the `bodytext` field in content element "table")
to an array or fetch a related record (e.g. a FAL file), so Fluid does not have to deal with this manipulation or transformation.


.. _AddingCE-use-an-extension:

Use an extension
================

We recommend to create your own extension for adding content elements.
The following example uses the extension key `your_extension_key`.
If you have plans to publish your extension, do not forget to check
for the availability of your desired key
and register it at the `"extension keys" page <https://typo3.org/extensions/extension-keys/>`_
(login for `typo3.org <https://typo3.org/>`_ is required).

Since this part is written for developers, it will not explain in full detail how an extension works.

.. _AddingCE-PageTSconfig:
.. _RegisterCE:
.. _AddingCE-TCA-Overrides-tt_content:

1. Register the content element
===============================

First add your new content element to the "New Content Element Wizard" and define its `CType` in PageTSconfig.
The example content element is called `yourextensionkey_newcontentelement`:

.. code-block:: typoscript

   mod.wizards.newContentElement.wizardItems.common {
       elements {
           yourextensionkey_newcontentelement {
               iconIdentifier = your-icon-identifier
               title = LLL:EXT:your_extension_key/Resources/Private/Language/Tca.xlf:yourextensionkey_newcontentelement.wizard.title
               description = LLL:EXT:your_extension_key/Resources/Private/Language/Tca.xlf:yourextensionkey_newcontentelement.wizard.description
               tt_content_defValues {
                   CType = yourextensionkey_newcontentelement
               }
           }
       }
       show := addToList(yourextensionkey_newcontentelement)
   }

You need to :ref:`register the icon identifier <t3coreapi:icon-registration>` with the icon API in your :file:`ext_localconf.php`.

Then you need to add the content element to the "Type" dropdown, where you can select
the type of content element in the file :file:`Configuration/TCA/Overrides/tt_content.php`:

.. code-block:: php

   // Adds the content element to the "Type" dropdown
   \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
       [
           'LLL:EXT:your_extension_key/Resources/Private/Language/Tca.xlf:yourextensionkey_newcontentelement',
           'yourextensionkey_newcontentelement',
           'EXT:your_extension_key/Resources/Public/Icons/ContentElements/yourextensionkey_newcontentelement.gif',
       ],
       'CType',
       'your_extension_key'
   );

.. _ConfigureCE-Fields:

2. Configure fields
===================

Then you need to configure the backend fields for your new content element in the file
:file:`Configuration/TCA/Overrides/tt_content.php`:

.. code-block:: php

   // Configure the default backend fields for the content element
   $GLOBALS['TCA']['tt_content']['types']['yourextensionkey_newcontentelement'] = [
       'showitem' => '
           --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
               --palette--;;general,
               --palette--;;headers,
               bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
           --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
               --palette--;;frames,
               --palette--;;appearanceLinks,
           --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
               --palette--;;language,
           --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
               --palette--;;hidden,
               --palette--;;access,
           --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
               categories,
           --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
               rowDescription,
           --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
       ',
       'columnsOverrides' => [
           'bodytext' => [
               'config' => [
                   'enableRichtext' => true,
                   'richtextConfiguration' => 'default',
               ],
           ],
       ],
   ];

.. _ConfigureCE-Frontend:

3. Configure the frontend template
==================================

Since TypoScript configuration is needed as well,add an entry in the static template list
found in `sys_templates` for static TypoScript in :file:`Configuration/TCA/Overrides/sys_template.php`:

.. code-block:: php

   // Add an entry in the static template list found in sys_templates for static TS
   \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
       'your_extension_key',
       'Configuration/TypoScript',
       'Your description'
   );

As defined in :file:`Configuration/TCA/Overrides/sys_template.php`, the file :file:`setup.typoscript` is in the directory
:file:`Configuration/TypoScript/` of your own extension.

To ensure your custom content element templates can be found you need to extend the global
:typoscript:`templateRootPaths` with a path within your extension:

.. code-block:: typoscript

   lib.contentElement {
       templateRootPaths {
           200 = EXT:your_extension_key/Resources/Private/Templates/
       }
   }

You can use an arbitrary index (`200` here), just make sure it is unique. If you use partials
and layouts, you need to do the same for :ref:`t3tsref:cobj-fluidtemplate-properties-partialrootpaths`
and :ref:`t3tsref:cobj-fluidtemplate-properties-layoutrootpaths`.

Now you can register the rendering of your custom content element using a Fluid template:

.. code-block:: typoscript

   tt_content {
       yourextensionkey_newcontentelement =< lib.contentElement
       yourextensionkey_newcontentelement {
           templateName = NewContentElement
       }
   }

In this example a :ref:`cobj-fluidtemplate` content object is created using a copy from :typoscript:`lib.contentElement`
with a template identified by the :ref:`t3tsref:cobj-fluidtemplate-properties-templatename` `NewContentElement`.
This will load a :file:`NewContentElement.html` template file from the :typoscript:`templateRootPaths`.

.. note::

   The :typoscript:`lib.contentElement` path is defined in
   :file:`EXT:fluid_styled_content/Configuration/TypoScript/Helper/ContentElement.typoscript`.


For the final rendering you need a Fluid template. This template will be located at the
directory and file name which you have entered in  :file:`setup.typoscript` using the parameter
:typoscript:`templateName`. Now we can use the `tt_content` fields in the Fluid template by accessing them
via the `data` variable. The following example shows the text entered in the richtext enabled
field `bodytext` formatted using the defined richtext configuration:

.. code-block:: html

   <div>{data.bodytext -> f:format.html()}</div>


.. _ConfigureCE-Preview:

4. Optional: configure custom backend preview
=============================================

If you want to generate a special preview in the backend "Web > Page" module, you can use
a hook for this:

.. code-block:: php

   // Register for hook to show preview of tt_content element of CType="yourextensionkey_newcontentelement" in page module
   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['yourextensionkey_newcontentelement'] =
      \Vendor\YourExtensionKey\Hooks\PageLayoutView\NewContentElementPreviewRenderer::class;

The preview renderer :file:`NewContentElementPreviewRenderer.php`, for the backend, has
been put in the directory :file:`Classes/Hooks/PageLayoutView/` and could look like this:

.. code-block:: php

   <?php
   namespace Vendor\YourExtensionKey\Hooks\PageLayoutView;

   /*
    * This file is part of the TYPO3 CMS project.
    *
    * It is free software; you can redistribute it and/or modify it under
    * the terms of the GNU General Public License, either version 2
    * of the License, or any later version.
    *
    * For the full copyright and license information, please read the
    * LICENSE.txt file that was distributed with this source code.
    *
    * The TYPO3 project - inspiring people to share!
    */

   use \TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
   use \TYPO3\CMS\Backend\View\PageLayoutView;

   /**
    * Contains a preview rendering for the page module of CType="yourextensionkey_newcontentelement"
    */
   class NewContentElementPreviewRenderer implements PageLayoutViewDrawItemHookInterface
   {

       /**
        * Preprocesses the preview rendering of a content element of type "My new content element"
        *
        * @param \TYPO3\CMS\Backend\View\PageLayoutView $parentObject Calling parent object
        * @param bool $drawItem Whether to draw the item using the default functionality
        * @param string $headerContent Header content
        * @param string $itemContent Item content
        * @param array $row Record row of tt_content
        *
        * @return void
        */
       public function preProcess(
           PageLayoutView &$parentObject,
           &$drawItem,
           &$headerContent,
           &$itemContent,
           array &$row
       ) {
           if ($row['CType'] === 'yourextensionkey_newcontentelement') {
               $itemContent .= '<p>We can change our preview here!</p>';

               $drawItem = false;
           }
       }
   }

.. _ConfigureCE-Extend-tt_content:

5. Optional: extend tt_content
==============================

.. todo::

   This will be filled in another patch.

.. _ConfigureCE-DataProcessors:

6. Optional: use data processors
================================

You can use data processors for some data manipulation or other actions you would like to perform before passing everything to the view.
This is done in the :ref:`dataProcessing <t3tsref:cobj-fluidtemplate-properties-dataprocessing>` section where you can add an arbitrary number of data processors,
each with a fully qualified class name (FQCN) and optional parameters to be used in the data processor:

.. code-block:: typoscript

   tt_content {
       yourextensionkey_newcontentelement =< lib.contentElement
       yourextensionkey_newcontentelement {
           templateName = NewContentElement
           dataProcessing {
               1 = Vendor\YourExtensionKey\DataProcessing\NewContentElementProcessor
               1 {
                   exampleOptionName = exampleOptionValue
               }
           }
       }
   }

In the example :file:`setup.typoscript` above, the data processor is located in the directory
:file:`Classes/DataProcessing/`. The file :file:`NewContentElementProcessor.php` could
look like this:

.. code-block:: php

   <?php
   declare(strict_types = 1);
   namespace Vendor\YourExtensionKey\DataProcessing;

   /*
    * This file is part of the TYPO3 CMS project.
    *
    * It is free software; you can redistribute it and/or modify it under
    * the terms of the GNU General Public License, either version 2
    * of the License, or any later version.
    *
    * For the full copyright and license information, please read the
    * LICENSE.txt file that was distributed with this source code.
    *
    * The TYPO3 project - inspiring people to share!
    */

   use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
   use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

   /**
    * Class for data processing for the content element "My new content element"
    */
   class NewContentElementProcessor implements DataProcessorInterface
   {

       /**
        * Process data for the content element "My new content element"
        *
        * @param ContentObjectRenderer $cObj The data of the content element or page
        * @param array $contentObjectConfiguration The configuration of Content Object
        * @param array $processorConfiguration The configuration of this processor
        * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
        * @return array the processed data as key/value store
        */
       public function process(
           ContentObjectRenderer $cObj,
           array $contentObjectConfiguration,
           array $processorConfiguration,
           array $processedData
       ) {
           $processedData['variableName'] = 'This variable will be passed to Fluid';

           return $processedData;
       }
   }


Just to show the variable `variableName`,
like defined in :ref:`ConfigureCE-Data-Processor`,
you can use the following markup:

.. code-block:: html

   <h1>{variableName}</h1>

