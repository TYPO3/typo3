.. include:: /Includes.rst.txt

.. _feature-97254:

=========================================================
Feature: #97254 - Add Luxembourgish as supported language
=========================================================

See :issue:`97254`

Description
===========

TYPO3 now supports Luxembourgish out of the box.

Luxembourgish is one of the three administrative languages of Luxembourg
(https://en.wikipedia.org/wiki/Luxembourgish).

The ISO 639-1 code for Luxembourgish is "lb", which is how TYPO3
is accessing the language internally.

Impact
======

It is now possible to

*   Fetch translated labels from translations.typo3.org / Crowdin automatically
    within the TYPO3 Backend
*   Switch the Backend Interface to Luxembourgish language
*   Create a new language in a site configuration using Luxembourgish
*   Create translation files with the "lb" prefix (such as `lb.locallang.xlf`)
    to create your own labels

and TYPO3 picks Luxembourgish as a language just like any other
supported language.

.. index:: Backend, Frontend, ext:core
