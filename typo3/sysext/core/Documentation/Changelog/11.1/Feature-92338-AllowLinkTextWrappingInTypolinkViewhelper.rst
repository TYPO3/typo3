.. include:: /Includes.rst.txt

================================================================
Feature: #92338 - Allow link text wrapping in TypolinkViewhelper
================================================================

See :issue:`92338`

Description
===========

Using the :html:`f:link.typolink` ViewHelper for generating links to internal
pages does now allow to wrap the automatically rendered link title, which
is usually the page title of the target page.

Therefore a new argument :html:`textWrap` is available, which can be used to
define the :typoscript:`wrap` setting for the typolink.

Defining :html:`<f:link.typolink parameter="123" textWrap="<span>|</span>"/>`
will generate :html:`<a href="some/site"><span>My page title</span></a>`.

.. note::

   When adding additional classes to the :html:`textWrap`, ensure quotes are correctly
   escaped: :html:`<f:link.typolink parameter="123" textWrap="<span class=\"my-class\">|</span>"/>`.

If :html:`textWrap` is set, the typolink option :php:`ATagBeforeWrap` is automatically
enabled, because the :typoscript:`wrap` should only be applied to the link text. Every
other use case can be handled in the fluid template itself.


Impact
======

It's now possible with the :html:`f:link.typolink` ViewHelper, to wrap the
automatically generated link text, e.g. when linking to an internal page.

.. index:: Fluid, ext:fluid
