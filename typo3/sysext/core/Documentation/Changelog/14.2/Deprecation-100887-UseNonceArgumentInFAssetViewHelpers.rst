..  include:: /Includes.rst.txt

..  _deprecation-100887-1774712028:

======================================================================================================
Deprecation: #100887 - Deprecation of useNonce argument in f:asset:css and f:asset:script view helpers
======================================================================================================

See :issue:`100887`

Description
===========

The :html:`useNonce` argument in the :html:`f:asset.script` and
:html:`f:asset.css` ViewHelpers has been renamed to :html:`csp` to better
reflect its purpose (controlling Content-Security-Policy hash/nonce
collection rather than nonce usage specifically).

Similarly, the :php:`'useNonce'` asset option key accepted by
:php:`addJavaScript()` and :php:`addStyleSheet()` in class
:php:`\TYPO3\CMS\Core\Page\AssetCollector` has been
replaced by :php:`'csp'`.


Impact
======

Passing :html:`useNonce` as a ViewHelper argument or as an
:php-short:`\TYPO3\CMS\Core\Page\AssetCollector` option key will trigger
a deprecation-level log entry in TYPO3 v14. This usage is scheduled for
removal in TYPO3 v15.


Affected installations
======================

Installations with Fluid templates using :html:`<f:asset.script useNonce="1">`
or :html:`<f:asset.css useNonce="1">`, and extensions calling
:php:`AssetCollector::addJavaScript()` or :php:`AssetCollector::addStyleSheet()`
with :php:`['useNonce' => true]`.


Migration
=========

Replace the :html:`useNonce` argument with :html:`csp` in Fluid templates:

..  code-block:: html

    <!-- Before -->
    <f:asset.script identifier="my-script"
        src="EXT:my_ext/Resources/Public/JavaScript/foo.js"
        useNonce="1" />

    <!-- After -->
    <f:asset.script identifier="my-script"
        src="EXT:my_ext/Resources/Public/JavaScript/foo.js"
        csp="1" />

Replace the :php:`'useNonce'` option key with :php:`'csp'` in PHP:

..  code-block:: php

    // Before
    $assetCollector->addJavaScript('my-script', $src, [], ['useNonce' => true]);

    // After
    $assetCollector->addJavaScript('my-script', $src, [], ['csp' => true]);

The :php-short:`\TYPO3\CMS\Core\Page\PageRenderer` methods :php:`addJsInlineCode()`,
:php:`addJsFooterInlineCode()`, and :php:`addCssInlineBlock()` retain their
:php:`$useNonce` parameter names for backward compatibility. No migration is
required for callers of these methods.


..  index:: Fluid, PHP-API, PartiallyScanned, ext:core, ext:fluid
