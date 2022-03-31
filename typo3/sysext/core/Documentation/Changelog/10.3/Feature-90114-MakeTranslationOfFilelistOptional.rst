.. include:: /Includes.rst.txt

=======================================================
Feature: #90114 - Make translation of filelist optional
=======================================================

See :issue:`90114`

Description
===========

The filelist module now takes :php:`$GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField']`
into account. By unsetting the field, translations in the filelist module are no longer possible.


Impact
======

If :php:`$GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField']` is set to an empty value,
translations are disabled for the filelist module.

.. index:: Backend, FAL, TCA, ext:filelist
