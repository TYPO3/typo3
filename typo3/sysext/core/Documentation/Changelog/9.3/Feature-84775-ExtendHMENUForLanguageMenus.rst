.. include:: /Includes.rst.txt

============================================================================================
Feature: #84775 - Extend HMENU to support auto filling of special.value for special=language
============================================================================================

See :issue:`84775`

Description
===========

This feature extends the :typoscript:`HMENU` content object to support the auto filling of
:typoscript:`special.value` for language menus with the site languages available for the
current site. Setting :typoscript:`special.value` to :typoscript:`auto` will include all available
languages from the current site.

In case of :typoscript:`special.value = auto` the register :typoscript:`languages_HMENU` will be set
with the determined IDs for the further usage in TypoScript.

Changed options
---------------

:`special.value`:  A list of comma separated language IDs (e.g. 0,1,2) or
                   :typoscript:`auto` to load the list from site languages

Example TypoScript configuration
--------------------------------

.. code-block:: typoscript

   10 = HMENU
   10 {
      special = language
      special.value = auto
   }

.. index:: Frontend, TypoScript
