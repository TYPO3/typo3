.. include:: ../Includes.txt


.. _adding-your-own-content-elements:

================================
Adding your own content elements
================================

.. note::

   This part is written for developers!

A content element can be based on already available fields in the `tt_content` table,
or it might be that you need extra fields. This is done the same way as you do for
your own extensions, extending TCA. Depending on the data in the `tt_content` table,
you can send the data immediately to the Fluid template or use a data processor in
front to do some data manipulation. The content elements in the extension "fluid_styled_content"
are using both as well. A data processor is sometimes used to convert a string (like
the `bodytext` field in content element "table") to an array, so Fluid does not
have to deal with this manipulation or transformation.


.. _AddingCE-use-an-extension:

Use an extension
================

Advisable is to make your own extension. In our example we've used the extension key
`your_extension_key`. If you have plans to publish your extension, do not forget to
lookup for the availability of your desired key and register it at the
`"extension keys" page <http://typo3.org/extensions/extension-keys/>`_. login in
`typo3.org <http://typo3.org//>`_ is required.

Since this part is written for developers, we will not explain in full detail how an
extension works.

.. _AddingCE-PageTSconfig:

PageTSconfig
------------
First we need to add our new content element to the "New Content Element Wizard" and
define its CType. We call it "yourextensionkey_newcontentelement".

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


.. _AddingCE-TCA-Overrides-tt_content:

Configuration/TCA/Overrides/tt_content.php
------------------------------------------

Then we need to add the content element to the "Type" dropdown, where you can select
the type of content element:

.. code-block:: php

   // Adds the content element to the "Type" dropdown
   \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
      array(
         'LLL:EXT:your_extension_key/Resources/Private/Language/Tca.xlf:yourextensionkey_newcontentelement',
         'yourextensionkey_newcontentelement',
         'EXT:your_extension_key/Resources/Public/Icons/ContentElements/yourextensionkey_newcontentelement.gif'
      ),
      'CType',
      'your_extension_key'
   );

Then we configure the backend fields for our new content element:

.. code-block:: php

   // Configure the default backend fields for the content element
   $GLOBALS['TCA']['tt_content']['types']['yourextensionkey_newcontentelement'] = array(
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
               'richtextConfiguration' => 'default'
            ]
         ]
      ]
   );

.. _AddingCE-TCA-Overrides-sys_template:

Configuration/TCA/Overrides/sys_template.php
--------------------------------------------

Since we need to use TypoScript as well, we add an entry in the static template list
found in sys_templates for static TS:

.. code-block:: php

   // Add an entry in the static template list found in sys_templates for static TS
   \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
      'your_extension_key',
      'Configuration/TypoScript',
      'Your description'
   );


.. _AddingCE-setup-txt:

setup.txt
---------

As defined in `Configuration/TCA/Overrides/tt_content.php`, this file is in the directory
`Configuration/TypoScript` of our own extension.

To ensure your custom content element templates can be found you need to extend the global
:typoscript:`templateRootPaths` with a path within your extension:

.. code-block:: typoscript

   lib.contentElement {
      templateRootPaths {
         200 = EXT:your_extension_key/Resources/Private/Templates/
      }
   }

You can use an arbitrary index (`200` here), just make sure it is unique. If you use partials
and layouts, you need to do the same for :typoscript:`partialRootPaths` and :typoscript:`layoutRootPaths`.

Now you can register the rendering of your custom content element using a Fluid template:

  .. code-block:: typoscript

     tt_content {
        yourextensionkey_newcontentelement =< lib.contentElement
        yourextensionkey_newcontentelement {
           templateName = NewContentElement
        }
     }

In this example a :typoscript:`FLUIDTEMPLATE` content object is created using a copy from
:typoscript:`lib.contentElement` with a template identified by the :typoscript:`templateName`
`NewContentElement`. This will load a `NewContentElement.html` template file from the
:typoscript:`templateRootPaths`.

.. note::

   The :typoscript:`lib.contentElement` path is defined in
   :file:`EXT:fluid_styled_content/Configuration/TypoScript/Helper/ContentElement.typoscript`.

You can use data processors for some data manipulation or other stuff you would like to do
before sending everything to the view. This is done in the :typoscript:`dataProcessing` section
where you can add an arbitrary number of data processors, each with a fully qualified class name
(FQCN) and optional parameters to be used in the data processor:

.. code-block:: typoscript

   tt_content {
      yourextensionkey_newcontentelement =< lib.contentElement
      yourextensionkey_newcontentelement {
         templateName = NewContentElement
         dataProcessing {
            1 = Vendor\YourExtensionKey\DataProcessing\NewContentElementProcessor
            1 {
               useHere = theConfigurationOfTheDataProcessor
            }
         }
      }
   }


.. _AddingCE-Data-Processor:

Data Processor
--------------

In our :ref:`AddingCE-setup-txt` example above, we put the data processor in the directory
:file:`Classes/DataProcessing`. The file :file:`NewContentElementProcessor.php` could
look like:

.. code-block:: php

   <?php
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
      )
      {
         $processedData['foo'] = 'This variable will be passed to Fluid';

         return $processedData;
      }
   }


.. _AddingCE-ext-localconf-php:

ext\_localconf.php
------------------

If you want to generate a special preview in the backend "Web > Page" module, you can use
a hook for this:

.. code-block:: php

   // Register for hook to show preview of tt_content element of CType="yourextensionkey_newcontentelement" in page module
   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['yourextensionkey_newcontentelement'] =
      \Vendor\YourExtensionKey\Hooks\PageLayoutView\NewContentElementPreviewRenderer::class;


.. _AddingCE-Content-Element-Preview-Renderer:

Content Element Preview Renderer
--------------------------------

The preview renderer :file:`NewContentElementPreviewRenderer.php`, for the backend, has
been put in the directory :file:`Classes/Hooks/PageLayoutView` and could look like this:

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
      )
      {
         if ($row['CType'] === 'yourextensionkey_newcontentelement') {
            $itemContent .= '<p>We can change our preview here!</p>';

            $drawItem = false;
         }
      }
   }


.. _AddingCE-fluid-templates:

Fluid templates
---------------

For the final rendering you need a Fluid template. This template will be located at the
directory and file name which you have entered in :ref:`AddingCE-setup-txt` using the parameter
`templateName`.

Just to show the variable foo, like we defined at :ref:`AddingCE-data-processor`,
we can use the following markup:

.. code-block:: html

   <h1>{foo}</h1>

