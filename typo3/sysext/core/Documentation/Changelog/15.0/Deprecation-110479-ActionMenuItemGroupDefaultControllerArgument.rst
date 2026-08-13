..  include:: /Includes.rst.txt

..  _deprecation-110479-1786640237:

=====================================================================
Deprecation: #110479 - ActionMenuItemGroup defaultController argument
=====================================================================

See :issue:`110479`

Description
===========

The unused :html:`defaultController` argument of the
:html:`<f:be.menus.actionMenuItemGroup>` ViewHelper has been deprecated and
will be removed in TYPO3 v16.0.

Impact
======

Using the :html:`defaultController` argument triggers a PHP
:php:`E_USER_DEPRECATED` error.

Affected installations
======================

TYPO3 installations with Fluid templates that pass the
:html:`defaultController` argument to
:html:`<f:be.menus.actionMenuItemGroup>` are affected.

Migration
=========

Remove the unused :html:`defaultController` argument from the ViewHelper call.

..  index:: Fluid, NotScanned, ext:fluid
