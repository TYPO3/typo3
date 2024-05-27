.. include:: /Includes.rst.txt

.. _feature-99510-1716815124:

================================================================
Feature: #99510 - Add file embedding option to asset viewhelpers
================================================================

See :issue:`99510`

Description
===========

The ViewHelpers :html:`<f:asset.css>` and :html:`<f:asset.script>` have
been extended with a new argument :html:`inline`. If this argument is set,
the referenced asset file is rendered inline.

Setting the argument will therefore load the file content of the defined
:html:`href` / :html:`src` as inline style or script. This is especially
useful for content elements which are used as first element on a page and
need some custom CSS to improve the Cumulative Layout Shift (CLS).

Impact
======

To add inline styles and scripts from a referenced file, the new :html:`inline`
argument can be set. For example, to add above-the-fold styles, the
:html:`priority` option can be set, which will put the file contents of
:file:`EXT:sitepackage/Resources/Public/Css/my-hero.css` as inline styles
to the :html:`<head>` section.

.. code-block:: html

    <f:asset.css identifier="my-hero" href="EXT:sitepackage/Resources/Public/Css/my-hero.css" inline="1" priority="1"/>

To add JavaScript:

.. code-block:: html

    <f:asset.script identifier="my-hero" src="EXT:sitepackage/Resources/Public/Js/my-hero.js" inline="1" priority="1"/>

.. index:: Fluid, Frontend, ext:fluid
