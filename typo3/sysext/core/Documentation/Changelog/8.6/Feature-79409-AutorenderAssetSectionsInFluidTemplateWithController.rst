.. include:: /Includes.rst.txt

===============================================================================
Feature: #73409 - Auto-render Assets sections in Fluid template with controller
===============================================================================

See :issue:`73409`


Description
===========

ActionController has received a new method, `renderAssetsForRequest` which receives the `RequestInterface` of the
Request currently being processed. The ActionController has a default implementation of this method which attempts
to render two sections in the Fluid template that is associated with the controller action being called:

* `<f:section name="HeaderAssets">` for assets intended for the `<head>` tag
* `<f:section name="FooterAssets">` for assets intended for the end of the `<body>` tag

Both sections are optional.

When rendering, `{request}` is available as template variable in both sections, as is `{arguments}`, allowing you
to make decisions based on various request/controller arguments. As usual, `{settings}` is also available.

All content you write into these sections will be output in the respective location as is, meaning you must write the entire
`<script>` or whichever tag you are writing, including all attributes. You can of course use various Fluid ViewHelpers
to resolve extension asset paths.

The feature only applies to ActionController (thus excluding CommandController) and will only attempt to render the
section if the view is an instance of :php:`TYPO3Fluid\\Fluid\\View\\TemplateView` (thus including any View in TYPO3 which
extends either TemplateView or AbstractTemplateView from TYPO3's Fluid adapter).


Impact
======

* Fluid templates rendered through any ActionController using a TemplateView may now contain two new sections for
  either `HeaderAssets` or `FooterAssets` depending on desired output. Content of these sections will be rendered
  and assigned via PageRenderer to either header or footer.
* ActionControllers can override the `renderAssetsForRequest` method to perform asset insertion using other means.
  The method sits at a very opportune point right after the action method itself gets called, when the entire controller
  is fully initialized with arguments etc. but no forwarding/redirection has happened in the controller action.

.. index:: Fluid, Frontend
