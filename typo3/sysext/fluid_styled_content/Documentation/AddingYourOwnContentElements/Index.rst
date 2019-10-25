.. include:: ../Includes.txt


.. _adding-your-own-content-elements:

================================
Adding your own content elements
================================

.. note::

   This part is written for developers!

A content element can be based on already available fields in the `tt_content` table
and/or extra fields you can add to the `tt_content` table. This is done the same way as you do for
your own extensions by extending TCA. Depending on the data in the `tt_content` table,
you can send the data immediately to the Fluid template or use a
:ref:`data processor <t3tsref:cobj-fluidtemplate-properties-dataprocessing>` in
front to do some data manipulation. The content elements in the extension "fluid_styled_content"
are using both as well. A data processor is sometimes used to convert a string (like
the `bodytext` field in content element "table") to an array or fetch a related record 
(e.g. a FAL file), so Fluid does not have to deal with this manipulation or transformation.


.. _AddingCE-use-an-extension:

Use an extension
================

We recommend to create your own extension for adding content objects.
The following example uses the extension key
`your_extension_key`. If you have plans to publish your extension, do not forget to
check for the availability of your desired key and register it at the
`"extension keys" page <http://typo3.org/extensions/extension-keys/>`_ (login for
`typo3.org <http://typo3.org//>`_ is required).

Since this part is written for developers, it will not explain in full detail how an
extension works.

To give a better understanding and make the actions more descriptive, we chose an image teaser element to be created in the following steps.

.. _AddingCE-PageTSconfig:
.. _RegisterCE:
.. _AddingCE-TCA-Overrides-tt_content:

1. Register the content element
===============================

First add your new content element to the "New Content Element Wizard" and define its CType in `PageTSconfig`.
The example content element is called "yourextensionkey_imageteaser":

.. code-block:: typoscript

   mod.wizards.newContentElement.wizardItems.common {
      elements {
         yourextensionkey_imageteaser {
            iconIdentifier = yourextensionkey_imageteaser
            title = LLL:EXT:your_extension_key/Resources/Private/Language/Tca.xlf:yourextensionkey_imageteaser.wizard.title
            description = LLL:EXT:your_extension_key/Resources/Private/Language/Tca.xlf:yourextensionkey_imageteaser.wizard.description
            tt_content_defValues {
               CType = yourextensionkey_imageteaser
            }
         }
      }
      show := addToList(yourextensionkey_imageteaser)
   }

You need to :ref:`register the icon identifier <t3coreapi:icon-registration>` with the icon API in your :file:`ext_localconf.php`.

Then you need to add the content element to the "Type" dropdown, where you can select
the type of content element in the file :file:`Configuration/TCA/Overrides/tt_content.php`:

.. code-block:: php

   // Adds the content element to the "Type" dropdown
   \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
      array(
         'LLL:EXT:your_extension_key/Resources/Private/Language/Tca.xlf:yourextensionkey_imageteaser',
         'yourextensionkey_imageteaser',
         'EXT:your_extension_key/Resources/Public/Icons/ContentElements/yourextensionkey_imageteaser.gif'
      ),
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
   $GLOBALS['TCA']['tt_content']['types']['yourextensionkey_imageteaser'] = [
      'showitem' => '
         --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;;general,
            --palette--;;headers,
            bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
            image,
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
         'image' => [
            'config' => [
               'minitems' => 1
               'maxitems' => 1
            ]
         ]
      ]
   ];

.. _ConfigureCE-Frontend:

3. Configure the frontend template
==================================

Since TypoScript configuration is needed as well, add an entry in the static template list
found in sys_templates for static TypoScript in :file:`Configuration/TCA/Overrides/sys_template.php`:

.. code-block:: php

   // Add an entry in the static template list found in sys_templates for static TS
   \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
      'your_extension_key',
      'Configuration/TypoScript',
      'Extension for content elements'
   );

As defined in :file:`Configuration/TCA/Overrides/tt_content.php`, the file :file:`setup.typoscript` is in the directory
:file:`Configuration/TypoScript` of your own extension.

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
        yourextensionkey_imageteaser =< lib.contentElement
        yourextensionkey_imageteaser {
           templateName = ImageTeaser
        }
     }

In this example a :typoscript:`FLUIDTEMPLATE` content object is created using a copy from
:typoscript:`lib.contentElement` with a template identified by the :typoscript:`templateName`
`ImageTeaser`. This will load a `ImageTeaser.html` template file from the
:typoscript:`templateRootPaths`.

.. note::

   The :typoscript:`lib.contentElement` path is defined in
   :file:`EXT:fluid_styled_content/Configuration/TypoScript/Helper/ContentElement.typoscript`.


For the final rendering you need a Fluid template. This template will be located at the
directory and file name which you have entered in  :file:`setup.typoscript` using the parameter
`templateName`. Now you can use the `tt_content` fields in the Fluid template by accessing them
via the `data` variable. The following example shows the text entered in the
field `bodytext` formatted to convert line breaks to `<br>` tags:

.. code-block:: html

   <div>
      <p>{data.bodytext -> f:format.nl2br()}<p>
   </div>


.. _ConfigureCE-DataProcessors:

4. Optional: use data processors
================================

As you have noticed, the image is missing in this output, because `data.image` only contains the amount of file references. To fetch the image as object, you can use a data processor, which processes and maniplates databefore passing everything to the view. This is done in the :typoscript:`dataProcessing` section where you can add an arbitrary number of data processors, each with a fully qualified class name (FQCN) and optional parameters to be used in the data processor:

.. code-block:: typoscript

   tt_content {
      yourextensionkey_imageteaser =< lib.contentElement
      yourextensionkey_imageteaser {
         templateName = ImageTeaser
         dataProcessing {
            1 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
            1 {
               references {
                  fieldName = image
                  table = tt_content
               }
               as = teaserImages
            }
         }
      }
   }

The :php:`FilesProcessor` resolves file references, files, or files inside a folder or collection to be used for output in the frontend. In this case it will return an array of file references of the field `image` which will be available as variable `teaserImages`. In the template you can simply fetch the first as exaclty one image was required in :file:`Configuration/TCA/Overrides/tt_content.php`:

.. code-block:: html

   <div>
      <f:image image="{teaserImages.0}" width="500" height="300c"/>
      <p>{data.bodytext -> f:format.nl2br()}<p>
   </div>

You can also write and use you own data processors. Just create a new class in the directory
:file:`Classes/DataProcessing`. The following example :file:`InputOutputProcessor.php` is just a simple input / output processor to demonstrate the usage:

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
    * Class for input / output data processing
    */
   class InputOutputProcessor implements DataProcessorInterface
   {

      /**
       * Modify and output inserted strings
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
         // Modify string and add variable
         $processedData['outputText'] = 'Magic ' . $processorConfiguration['inputText'];

         return $processedData;
      }
   }

Now you can call your data processor in the :file:`setup.typoscript` just like the :php:`FilesProcessor` before and use the specified argument `inputText`:


.. code-block:: typoscript

   tt_content {
      yourextensionkey_imageteaser =< lib.contentElement
      yourextensionkey_imageteaser {
         templateName = ImageTeaser
         dataProcessing {
            1 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
            1 {
               references {
                  fieldName = image
                  table = tt_content
               }
               as = teaserImages
            }

            2 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
            2 {
               inputText = clik
            }
         }
      }
   }

The variable `outputText` specified in  :file:`InputOutputProcessor.php` can now be used in the template:

.. code-block:: html

   <div>
      <f:image image="{teaserImages.0}" width="500" height="300c"/>
      <p>{data.bodytext -> f:format.nl2br()}<p>
      <p>{outputText}</p>
   </div>



.. _ConfigureCE-Extend-tt_content:

5. Optional: add custom fields
==============================

For your content element you can add a custom field by :ref:`extending tt_content <t3coreapi:columns-input-examples>`. You can either add the new field to all TCA types (meaning CTypes in this case) like described in the linked document or only add it to your custom content element (CType). As the image teaser is currently missing a link, the following example shows how to add a custom link field `tx_yourextensionkey_link` (:ref:`see example link field configuration <t3tca:extending-examples-ttcontent>`) to the image teaser only in 
:file:`Configuration/TCA/Overrides/tt_content.php`:

.. code-block:: php

   // Add link field
   $newColumns = [
      'tx_yourextensionkey_link' => [
         // ...
      ]
   ];

   \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', $newColumns);

   // Configure the default backend fields for the content element
   $GLOBALS['TCA']['tt_content']['types']['yourextensionkey_imageteaser'] = [
      'showitem' => '
         --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;;general,
            --palette--;;headers,
            bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
            image,
            tx_yourextensionkey_link,
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
         // ...
      ]
   ];

The new field is then added to the `data` array and can be accessed just like the other `tt_content` fields:

.. code-block:: html

   <div>
      <f:image image="{teaserImages.0}" width="500" height="300c"/>
      <p>{data.bodytext -> f:format.nl2br()}<p>
      <f:link.typolink parameter="{data.tx_yourextensionkey_link}">{outputText}</f:link.typolink>
   </div>


.. _ConfigureCE-Preview:

6. Optional: configure custom backend preview
=============================================

If you want to generate a special preview in the backend "Web > Page" module, you can simply override the template file in `PageTSconfig`:

.. code-block:: typoscript

   mod.web_layout.tt_content.preview.yourextensionkey_imageteaser = EXT:your_extension_key/Resources/Private/Templates/Preview/ImageTeaser.html

Beware that, although the fields are also assigned like in the frontend template, they are not grouped in the `data` variable and no data processors are applied. This would be an example of the image teaser preview without the image and processd text:

.. code-block:: html

   <p>{bodytext -> f:format.nl2br()}<p>
   Link: <f:uri.typolink parameter="{tx_yourextensionkey_link}">

Or you can use a hook for this:

.. code-block:: php

   // Register for hook to show preview of tt_content element of CType "yourextensionkey_imageteaser" in page module
   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['yourextensionkey_imageteaser'] =
      \Vendor\YourExtensionKey\Hooks\PageLayoutView\ImageTeaserPreviewRenderer::class;

The preview renderer :file:`ImageTeaserPreviewRenderer.php`, for the backend, should be located in the directory :file:`Classes/Hooks/PageLayoutView` and could look like this:

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
    * Contains a preview rendering for the page module of CType "yourextensionkey_imageteaser"
    */
   class ImageTeaserPreviewRenderer implements PageLayoutViewDrawItemHookInterface
   {

      /**
       * Preprocesses the preview rendering of the image teaser
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
         if ($row['CType'] === 'yourextensionkey_imageteaser') {
            $itemContent .= '<p>This is a custom preview!</p>';

            $drawItem = false;
         }
      }
   }


