..  include:: /Includes.rst.txt

..  _feature-108726-1769503907:

=============================================================
Feature: #108726 - Introduce Fluid f:render.record ViewHelper
=============================================================

See :issue:`108726`

Description
===========

Instead of using the :html:`<f:cObject>` ViewHelper to render database records,
the new :html:`<f:render.record>` ViewHelper can be used.

It allows rendering records while enabling other extensions to modify the output via PSR-14 EventListeners.

This is especially useful for adding debugging wrappers or additional HTML structure
around content elements.

By default, the ViewHelper renders the record as-is, but EventListeners
can listen to the :php:`\TYPO3\CMS\Fluid\Event\ModifyRenderedRecordEvent` and modify the output.

Usage with the `record-transformation` data processor:

..  code-block:: typoscript

    dataProcessing {
        10 = record-transformation
    }


..  code-block:: html
    :caption: MyContentElement.fluid.html

    <f:render.record record="{record}"/>
    or
    {record -> f:render.record()}

You can not only render tt_content records but any database record by defining a the rendering in Typoscript.

..  code-block:: typoscript


    # Example Typoscript configuration for rendering custom records
    sys_category = FLUIDTEMPLATE
    sys_category {
      file = EXT:my_extension/Resources/Private/Templates/Category.html
      layoutRootPaths.10 = EXT:my_extension/Resources/Private/Layouts/
      partialRootPaths.10 = EXT:my_extension/Resources/Private/Partials/
      dataProcessing.1421884800 = record-transformation
    }

    # Example Typoscript configuration for special record types
    tx_myextension_domain_model_product = COA
    tx_myextension_domain_model_product.default = FLUIDTEMPLATE
    tx_myextension_domain_model_product.default {
      templateName >
      templateName.ifEmpty.cObject = TEXT
      templateName.ifEmpty.cObject {
        field = record_type
        required = 1
        case = uppercamelcase
      }
      # for record_type = 'mainProduct' the template file my_extension/Resources/Private/Templates/Product/MainProduct.html will be used
      layoutRootPaths.10 = EXT:my_extension/Resources/Private/Layouts/
      partialRootPaths.10 = EXT:my_extension/Resources/Private/Partials/
      templateRootPaths.10 = EXT:my_extension/Resources/Private/Templates/Product/
      dataProcessing.1421884800 = record-transformation
    }

Impact
======

Theme creators are encouraged to use the :html:`<f:render.record>` ViewHelper
to allow other extensions to modify the output via EventListeners.


..  index:: Frontend, ext:fluid
