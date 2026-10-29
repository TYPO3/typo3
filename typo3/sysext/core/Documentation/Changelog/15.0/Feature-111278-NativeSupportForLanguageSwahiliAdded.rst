..  include:: /Includes.rst.txt

..  _feature-111278-1793294270:

============================================================
Feature: #111278 - Native support for language Swahili added
============================================================

See :issue:`111278`

Description
===========

TYPO3 now supports Swahili. Swahili is an official language of Kenya, Tanzania,
Uganda, and the African Union, and the most widely spoken language of
sub-Saharan Africa.

The ISO 639-1 code for Swahili is `sw`, which is how TYPO3 accesses the
language internally.

Impact
======

It is now possible to

*   fetch translated labels from Crowdin automatically inside the TYPO3 backend
*   switch the backend interface to Swahili
*   create translation files with the `sw` prefix (such as
    `sw.locallang.xlf`) to define custom labels

TYPO3 will treat Swahili like any other supported language.

..  index:: Backend, Frontend, ext:core
