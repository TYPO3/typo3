.. include:: ../../Includes.txt

====================================================================================
Feature: #40729 - Title attribute for (un)substituted constants in TS object browser
====================================================================================

See :issue:`40729`

Description
===========

The TypoScript object browser Backend Module comes now with a tiny improvement for
the "(un)substituted constants" view.
When hovering over an item in the object browser, the constant name will be shown when in
"substituted constants in green" mode, or the constant value when in "unsubstituted constants in
green" mode.

This way one doesn't necessarily have to toggle between the two "green modes".


Impact
======

Less toggling between the two "contants in green" modes while using the TS object browser.
And as a side effect this patch improves the search feature of the object browser when searching
for a constant value or name, i.e. 'maxWInText' while being on "substituted" mode.

.. index:: Backend, TypoScript, ext:tstemplate
