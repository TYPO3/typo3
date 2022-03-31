.. include:: /Includes.rst.txt

=============================================================================
Feature: #79413 - Auto-render Assets sections in FLUIDTEMPLATE content object
=============================================================================

See :issue:`79413`


Description
===========

FLUIDTEMPLATE content object will now automatically render two sections and insert the rendered content as assets via
PageRenderer. The two sections can be defined in the template file rendered by the FLUIDTEMPLATE object.

* `<f:section name="HeaderAssets">` for assets intended for the `<head>` tag
* `<f:section name="FooterAssets">` for assets intended for the end of the `<body>` tag

Both sections are optional.

When rendering, `{contentObject}` is available as template variable in both sections, allowing you to make decisions
based on various aspects of the configured content object instance. In addition, all variables you declared for the
content object are available when rendering either section.

All content you write into these sections will be output in the respective location as is, meaning you must write the entire
`<script>` or whichever tag you are writing, including all attributes. You can of course use various Fluid ViewHelpers
to resolve extension asset paths.


Impact
======

* Fluid templates rendered through the FLUIDTEMPLATE content object may now contain two new sections for either
  `HeaderAssets` or `FooterAssets` depending on desired output. Content of these sections will be rendered
  and assigned via PageRenderer to either header or footer.

.. index:: Fluid, Frontend
