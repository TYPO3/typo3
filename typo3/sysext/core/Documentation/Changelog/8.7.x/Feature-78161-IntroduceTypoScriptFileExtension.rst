.. include:: ../../Includes.txt

======================================================
Feature: #78161 - Introduce .typoscript file extension
======================================================

See :issue:`78161`

Description
===========

The new file extension .typoscript will be the default for TypoScript configuration
files and is the only recommended one from now on. This effort is made to introduce
a dedicated file extension for TypoScript configuration files, and to avoid conflicts
with already existing and more spread file extensions like ".ts" for TypeScript or
Video Transport Stream Files.

New prioritised files for static templates:
* constants.typoscript
* setup.typoscript

New prioritised files for extension statics:
* ext_typoscript_constants.typoscript
* ext_typoscript_setup.typoscript

For more details please head over to the decision platform:
* https://decisions.typo3.org/t/file-endings-for-typoscript-files-and-tsconfig-files/43
* https://decisions.typo3.org/t/file-endings-for-typoscript-and-tsconfig-files-results/71


Impact
======

The ".typoscript" file extension is prioritised over the legacy .txt and .ts file
extensions, and the only recommended file extension for typoscript configuration
files.


.. index:: Frontend, TypoScript
