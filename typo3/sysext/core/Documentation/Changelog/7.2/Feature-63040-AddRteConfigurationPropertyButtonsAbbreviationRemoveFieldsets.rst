
.. include:: /Includes.rst.txt

=====================================================================================
Feature: #63040 - Add RTE configuration property buttons.abbreviation.removeFieldsets
=====================================================================================

See :issue:`63040`

Description
===========

The new property `buttons.abbreviation.removeFieldsets` may be used in Page TSconfig
to configure the abbreviation dialogue.

If set, the listed fieldsets of the Abbreviation dialogue are not shown.

Possible values in the list are: acronym, definedAcronym, abbreviation, definedAbbreviation


Impact
======

The acronym tag is deprecated in HTML5. Installations that want to use the Abbreviation
feature of the RTE, but do not wish to use the acronym setting tab of the Abbreviation
dialogue, may set this property in the Page TSconfig of the RTE, specifying
`buttons.abbreviation.removeFieldsets = acronym,definedAcronym`


.. index:: TSConfig, RTE, Backend
