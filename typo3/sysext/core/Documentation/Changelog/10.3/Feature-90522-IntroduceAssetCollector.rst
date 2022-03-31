.. include:: /Includes.rst.txt

.. _changelog-Feature-90522-IntroduceAssetCollector:

==========================================
Feature: #90522 - Introduce AssetCollector
==========================================

See :issue:`90522`

Description
===========

AssetCollector is a concept to allow custom CSS/JS code, inline or external, to be added multiple
times in e.g. a Fluid template (via :html:`<f:asset.script>` or :html:`<f:asset.css>` ViewHelpers) but only rendered once
in the output.

The :php:`priority` flag (default: :php:`false`) controls where the asset is included:

* JavaScript will be output inside :html:`<head>` (:php:`priority=true`) or at the bottom of the :html:`<body>` tag (:php:`priority=false`)
* CSS will always be output inside :html:`<head>`, yet grouped by :js:`priority`.

By including assets per-component, it can leverage the adoption of HTTP/2 multiplexing which removes the necessity of having all CSS/JavaScript
concatenated into one file.

AssetCollector is implemented as singleton and should slowly replace the various existing options
in TypoScript.

AssetCollector also collects information about "imagesOnPage", effectively taking off pressure from
PageRenderer and TSFE to store common data in FE - as this is now handled in AssetCollector,
which can be used in cached and non-cached components.

The new API
-----------

- :php:`\TYPO3\CMS\Core\Page\AssetCollector::addJavaScript(string $identifier, string $source, array $attributes, array $options = []): self`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::addInlineJavaScript(string $identifier, string $source, array $attributes, array $options = []): self`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::addStyleSheet(string $identifier, string $source, array $attributes, array $options = []): self`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::addInlineStyleSheet(string $identifier, string $source, array $attributes, array $options = []): self`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::addMedia(string $fileName, array $additionalInformation): self`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::removeJavaScript(string $identifier): self`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::removeInlineJavaScript(string $identifier): self`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::removeStyleSheet(string $identifier): self`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::removeInlineStyleSheet(string $identifier): self`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::removeMedia(string $identifier): self`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::getJavaScripts(?bool $priority = null): array`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::getInlineJavaScripts(?bool $priority = null): array`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::getStyleSheets(?bool $priority = null): array`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::getInlineStyleSheets(?bool $priority = null): array`
- :php:`\TYPO3\CMS\Core\Page\AssetCollector::getMedia(): array`

New ViewHelpers
---------------

There are also two new ViewHelpers, the :html:`<f:asset.css>` and the - :html:`<f:asset.script>` ViewHelper which use the AssetCollector API.

.. code-block:: html

   <f:asset.css identifier="identifier123" href="EXT:my_ext/Resources/Public/Css/foo.css" />
   <f:asset.css identifier="identifier123">
      .foo { color: black; }
   </f:asset.css>

   <f:asset.script identifier="identifier123" src="EXT:my_ext/Resources/Public/JavaScript/foo.js" />
   <f:asset.script identifier="identifier123">
      alert('hello world');
   </f:asset.script>

Considerations
--------------

Currently, assets registered with the AssetCollector are not included in callbacks of these hooks:

- :php:`$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssCompressHandler']`
- :php:`$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsCompressHandler']`
- :php:`$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssConcatenateHandler']`
- :php:`$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsConcatenateHandler']`

.. versionadded:: 10.4

   Events for the new API have been introduced in
   :ref:`changelog-Feature-90899-IntroduceAssetPreRenderingEvents`

Currently, CSS and JavaScript registered with the AssetCollector will be rendered after their
PageRenderer counterparts. The order is:

- :html:`<head>`
- :typoscript:`page.includeJSLibs.forceOnTop`
- :typoscript:`page.includeJSLibs`
- :typoscript:`page.includeJS.forceOnTop`
- :typoscript:`page.includeJS`
- :php:`AssetCollector::addJavaScript()` with 'priority'
- :typoscript:`page.jsInline`
- :php:`AssetCollector::addInlineJavaScript()` with 'priority'
- :html:`</head>`

- :typoscript:`page.includeJSFooterlibs.forceOnTop`
- :typoscript:`page.includeJSFooterlibs`
- :typoscript:`page.includeJSFooter.forceOnTop`
- :typoscript:`page.includeJSFooter`
- :php:`AssetCollector::addJavaScript()`
- :typoscript:`page.jsFooterInline`
- :php:`AssetCollector::addInlineJavaScript()`

Currently, JavaScript registered with AssetCollector is not affected by
:typoscript:`config.moveJsFromHeaderToFooter`.

Examples
--------

Add a JavaScript file to the collector with script attribute data-foo="bar":

.. code-block:: php

    GeneralUtility::makeInstance(AssetCollector::class)
       ->addJavaScript('my_ext_foo', 'EXT:my_ext/Resources/Public/JavaScript/foo.js', ['data-foo' => 'bar']);

Add a JavaScript file to the collector with script attribute :html:`data-foo="bar"` and priority which means rendering before other script tags:

.. code-block:: php

    GeneralUtility::makeInstance(AssetCollector::class)
       ->addJavaScript('my_ext_foo', 'EXT:my_ext/Resources/Public/JavaScript/foo.js', ['data-foo' => 'bar'], ['priority' => true]);

Add a JavaScript file to the collector with :html:`type="module"` (by default, no type= is output for JavaScript):

.. code-block:: php

    GeneralUtility::makeInstance(AssetCollector::class)
       ->addJavaScript('my_ext_foo', 'EXT:my_ext/Resources/Public/JavaScript/foo.js', ['type' => 'module']);

.. index:: Backend, Frontend, PHP-API, ext:core
