..  include:: /Includes.rst.txt

..  _feature-109214:

=========================================================
Feature: #109214 - Native support for language Lao added
=========================================================

See :issue:`109214`

Description
===========

TYPO3 now supports Lao (sometimes referred to as "Laotian"). Lao is the official
language of Laos.

The ISO 639-1 code for Lao is `lo`, which is how TYPO3 accesses the language
internally.

Impact
======

It is now possible to

*   fetch translated labels from Crowdin automatically inside the TYPO3 backend
*   switch the backend interface to Lao
*   create translation files with the `lo` prefix (such as
    `lo.locallang.xlf`) to define custom labels

TYPO3 will treat Lao like any other supported language.

..  index:: Backend, Frontend, ext:core
