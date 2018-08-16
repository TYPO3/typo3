.. include:: ../../Includes.txt

======================================================
Feature: #85164 - Enable Languages on a per-site basis
======================================================

See :issue:`85164`

Description
===========

When configuring a new site with multiple languages, is it now possible to not allow a language to be rendered
in the TYPO3 Frontend. A new checkbox in the Site Handling module allows to add a language but not render it in
Frontend to allow to prepare a new translation of a website before it is going live.


Impact
======

Previously this wasn't as easy as doing this with one click, and took various places into account to switch
a translation of a website "live". Going live is now as easy as turning on one checkbox.

.. index:: Frontend
