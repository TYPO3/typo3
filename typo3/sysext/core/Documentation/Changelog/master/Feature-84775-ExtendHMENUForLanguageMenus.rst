.. include:: ../../Includes.txt

============================================================================================
Feature: #84775 - Extend HMENU to support auto filling of special.value for special=language
============================================================================================

See :issue:`84775`

Description
===========

This feature extends the `HMENU` content object to support the auto filling of
`special.value` for language menus with the site languages available for the
current site. Setting `special.value` to `auto` will include all available
languages from the current site.

In case of `special.value = auto` the register `languages_HMENU` will be set
with the determined IDs for the further usage in TypoScript.

Changed options
---------------

:`special.value`:  A list of comma separated language IDs (e.g. 0,1,2) or
                   `auto` to load the list from site languages

Example TypoScript configuration
--------------------------------

.. code-block:: typoscript

   10 = HMENU
   10 {
      special = language
      special.value = auto
   }

.. index:: Frontend, TypoScript
