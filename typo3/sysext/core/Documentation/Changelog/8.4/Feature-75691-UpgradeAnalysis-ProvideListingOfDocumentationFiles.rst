.. include:: /Includes.rst.txt

===========================================================================
Feature: #75691 - Upgrade Analysis - Provide listing of documentation files
===========================================================================

See :issue:`75691`

Description
===========

The install tool now shows all the documentation files that were delivered with the core
in the section `Upgrade analysis`. All files can be read inline, but there is no
parsing, plain `.rst` is shown to the user.


Impact
======

The install tool features a new main entry point that lists the documentation files shipped with
the core. Filtering by tags provided in the documentation files helps to find interesting changes.

.. index:: Backend
