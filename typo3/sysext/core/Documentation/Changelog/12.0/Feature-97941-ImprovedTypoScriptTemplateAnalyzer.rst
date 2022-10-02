.. include:: /Includes.rst.txt

.. _feature-97941-1657809445:

=======================================================
Feature: #97941 - Improved TypoScript Template Analyzer
=======================================================

See :issue:`97941`

Description
===========

The backend "Template" module "Template Analyzer" got a major overhaul and
displays much more information than before:

* The rendering separates "constant" and "setup" includes and renders
  both in own panels.
* :typoscript:`@import` and :typoscript:`<INCLUDE_TYPOSCRIPT:` are now resolved
  and shown as nodes within the include tree.
* TypoScript conditions are reflected in the include tree and can be toggled
  to simulate frontend condition verdicts.
* Clicking an include node displays this section of the include tree as source
  tree with appropriate comments for import statements.

Impact
======

The "Template Analyzer" is now based on the
:ref:`new TypoScript Parser <breaking-97816-1656350406>`
and gives more information to integrators and administrators.

.. index:: Backend, TypoScript, ext:tstemplate
