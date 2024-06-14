.. include:: /Includes.rst.txt

.. _feature-104020-1718381897:

====================================================
Feature: #104020 - ViewHelper to check feature flags
====================================================

See :issue:`104020`

Description
===========

The `<f:feature>` ViewHelper allows integrators to check for feature flags from within Fluid
templates. The ViewHelper follows the same rules as the underlying TYPO3 api, which means
that undefined flags will be treated as `false`.

Examples
========

Basic usage
-----------

::

   <f:feature name="myFeatureFlag">
      This is being shown if the flag is enabled
   </f:feature>

feature / then / else
---------------------

::

   <f:feature name="myFeatureFlag">
      <f:then>
         Flag is enabled
      </f:then>
      <f:else>
         Flag is undefined or not enabled
      </f:else>
   </f:feature>


Impact
======

Feature flags can now be checked from within Fluid templates.

.. index:: Fluid, ext:fluid
