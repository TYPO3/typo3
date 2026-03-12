.. include:: /Includes.rst.txt

.. _feature-109214:

=========================================================
Feature: #109214 - Native support for language Lao added
=========================================================

See :issue:`109214`

Description
===========

TYPO3 now supports Lao (sometimes referred to as "Laotian"). Lao is the official
language in Laos.

The ISO 639-1 code for Lao is "lo", which is how TYPO3 is accessing the language
internally.

Impact
======

It is now possible to

*  Fetch translated labels from Crowdin automatically within the TYPO3 Backend
*  Switch the backend interface to Lao language
*  Create translation files with the "lo" prefix (such as `lo.locallang.xlf`)
   to create your own labels

and TYPO3 will pick Lao as a language just like any other supported language.

.. index:: Backend, Frontend, ext:core
