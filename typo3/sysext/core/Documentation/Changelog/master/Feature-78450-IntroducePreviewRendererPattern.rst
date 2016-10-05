.. include:: ../../Includes.txt

===================================================
Feature: #78450 - Introduce PreviewRenderer pattern
===================================================

See :issue:`78450`

Pre-requisites
==============

The PreviewRenderer usage is only active if the "fluid based page layout module" feature is enabled. This feature
is enabled by default in TYPO3 versions 10.3 and later.

The feature toggle can be located in the `Settings` admin module under `Feature Toggles`. Or it can be set in
PHP using :php:``$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['fluidBasedPageModule'] = true;``.


Description
===========

A new pattern has been introduced to facilitate (record) previews in TYPO3. A default implementation has been
added which provides support for the previous methods of generating previews (content previews - using hooks
or by defining a Fluid template to render).

The new pattern creates a strict contract for code which generates such previews and enables switching out the
implementation of both the resolving logic (which finds a preview renderer for a given table and record) as well
as the rendering logic (which now renders both the actual preview and has contract methods for adding wrapping).

The main differences between the old and the new approach are:

* The class used to render previews is now defined in TCA and can be defined per-type or for any type.
* The resolver used to find preview renderers is a global implementation overridable in configuration.
* A single preview renderer will now be used; before, hook subscribers had to toggle passed-by-reference flags.
* Wrapping is no longer forced to be a `<span>` tag so you are not restricted to inline and inline-block display.
* Preview renderers have a public contract which splits up actual preview and wrapping, allowing third party renderers
  to subclass the original renderer and for example only change the wrapping tag.
* Preview rendering can now be done ad-hoc through; the pattern can be used from any context where the old pattern
  could only be used (was only used) in the PageLayoutView for content previews.


Impact
======

The feature adds two new concepts:

* `PreviewRendererResolver` which is a global implementation to detect which `PreviewRenderer` a given record needs.
* `PreviewRenderer` which is the class responsible for generating the preview and the wrapping.


Configuring the implementation
------------------------------

The PreviewRendererResolver can be overridden by setting:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['previewRendererResolver'] = \TYPO3\CMS\Backend\Preview\StandardPreviewRendererResolver::class;


(the class shown is the standard implementation TYPO3 provides, inspect this class for further developer information)

Once overridden the old resolver will no longer be consulted.

And individual preview renderers can be defined using one of the following two approaches:

.. code-block:: php

    $GLOBALS['TCA'][$table]['ctrl']['previewRenderer'] = My\PreviewRenderer::class;


Which specifies the PreviewRenderer to use for any record in `$table`

Or if your table has a "type" field/attribute:

.. code-block:: php

    $GLOBALS['TCA'][$table]['types'][$type]['previewRenderer'] = My\PreviewRenderer::class;

Which specifies the PreviewRenderer for only records of type `$type` as determined by the type field of your table.

Or finally, if your table and field has a `subtype_value_field` TCA setting (like `tt_content.list_type` for example)
and you want register a preview renderer that applies only when that value is selected (e.g. when a certain plugin type
is selected and you can't match it with the "type" of the record alone):

.. code-block:: php

    $GLOBALS['TCA'][$table]['types'][$type]['previewRenderer'][$subType] = My\PreviewRenderer::class;

Where `$type` is for example `list` (indicating a plugin) and `$subType` is the value of the `list_type` field when the
type of plugin you want to target, is selected as plugin type.

Note: recommended location is in the `ctrl` array in your extension's `Configuration/TCA/$table.php` or
`Configuration/TCA/Overrides/$table.php` file. The former is used when your extension is the one that creates the table -
the latter is used when you need to override TCA properties of tables added by the core or other extensions.


The PreviewRenderer interface
-----------------------------

`\TYPO3\CMS\Backend\Preview\PreviewRendererResolverInterface` must be implemented by PreviewRendererResolvers and
contains a single API method, `public function resolveRendererFor($table, array $row, int $pageUid);` which
unsurprisingly returns a single PreviewRenderer based on the given input.

`\TYPO3\CMS\Backend\Preview\PreviewRendererInterface` must be implemented by any PreviewRenderer and contains a few
API methods:

.. code-block:: php
    /**
     * Dedicated method for rendering preview header HTML for
     * the page module only. Receives $item which is an instance
     * GridColumnItem which has a getter method to return the record.
     *
     * @param GridColumnItem
     * @return string
     */
    public function renderPageModulePreviewHeader(GridColumnItem $item);

    /**
     * Dedicated method for rendering preview body HTML for
     * the page module only.
     *
     * @param GridColumnItem $item
     * @return string
     */
    public function renderPageModulePreviewContent(GridColumnItem $item);

    /**
     * Render a footer for the record to display in page module below
     * the body of the item's preview.
     *
     * @param GridColumnItem $item
     * @return string
     */
    public function renderPageModulePreviewFooter(GridColumnItem $item): string;

    /**
     * Dedicated method for wrapping a preview header and body HTML.
     *
     * @param string $previewHeader
     * @param string $previewContent
     * @param GridColumnItem $item
     * @return string
     */
    public function wrapPageModulePreview($previewHeader, $previewContent, GridColumnItem $item);

With further methods expected to be added to support generic preview rendering, e.g. usages outside PageLayoutView.
Implementing these methods allows you to control the exact composition of the preview.

This means assuming your PreviewRenderer returns `<h4>Header</h4>` from the header render method and `<p>Body</p>` from
the preview content rendering method and your wrapping method does `return '<div>' . $previewHeader . $previewContent . '</div>';` then the
entire output becomes `<div><h4>Header</h4><p>Body</p></div>` when combined.

Should you wish to reuse parts of the default preview rendering and only change, for example, the method that renders
the preview body content, you can subclass ``\TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer`` in your
own PreviewRenderer class - and selectively override the methods from the API displayed above.

.. index:: Backend, TCA
