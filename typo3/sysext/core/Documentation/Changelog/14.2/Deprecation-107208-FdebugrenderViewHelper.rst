..  include:: /Includes.rst.txt

..  _deprecation-107208-1754387701:

==================================================
Deprecation: #107208 - <f:debug.render> ViewHelper
==================================================

See :issue:`107208`

Description
===========

The `<f:debug.render>` ViewHelper has been deprecated. It was used internally to
render Fluid debug output for the admin panel.


Impact
======

Calling the ViewHelper from a template triggers a deprecation warning. The
ViewHelper will be removed in TYPO3 v15.

Affected installations
======================

Projects and extensions that use `<f:debug.render>` in a template.


Migration
=========

A custom ViewHelper can be created that mimics the behavior of the Core ViewHelper.

..  index:: Fluid, NotScanned, ext:fluid
