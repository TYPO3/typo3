
.. include:: /Includes.rst.txt

==========================================================================
Feature: #34922 - Allow .ts file extension for static TypoScript templates
==========================================================================

See :issue:`34922`

Description
===========

Only these TypoScript file names were allowed:

- constants.txt
- setup.txt
- include_static.txt
- include_static_files.txt

The ts file extension has been allowed for constants and setup and is prioritised over txt.


Impact
======

There is a little performance impact when loading the TypoScript from scratch like in the backend and frontend without
cache as the new file extension is always tested.


.. index:: Frontend, TypoScript
