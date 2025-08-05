..  include:: /Includes.rst.txt

..  _deprecation-107208-1754387701:

==================================================
Deprecation: #107208 - <f:debug.render> ViewHelper
==================================================

See :issue:`107208`

Description
===========

The `<f:debug.render>` ViewHelper, which has been used internally to render the
Fluid debug output for the admin panel, has been deprecated.


Impact
======

Calling the ViewHelper from a template will trigger a deprecation warning. The
ViewHelper will be removed with TYPO3 v15.


Affected installations
======================

Projects or extensions that use `<f:debug.render>` in a template.


Migration
=========

A custom ViewHelper can be created that mimics the behavior of the Core ViewHelper.

..  index:: Fluid, NotScanned, ext:fluid
