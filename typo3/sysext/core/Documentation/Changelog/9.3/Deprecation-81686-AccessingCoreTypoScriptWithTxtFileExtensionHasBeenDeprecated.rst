.. include:: /Includes.rst.txt

============================================================================================
Deprecation: #81686 - Accessing core TypoScript with .txt file extension has been deprecated
============================================================================================

See :issue:`81686`

Description
===========

TYPO3 Core TypoScript files were renamed from :file:`.txt` extension to :file:`.typoscript` and :file:`.tsconfig`.
The backward compatibility layer has been introduced for :typoscript:`<INCLUDE_TYPOSCRIPT` inclusion.
If including file with :file:`.txt` does not exist, then TYPO3 will try to load a file with :file:`.typoscript` extension.


Impact
======

Installations including Core TypoScript using old file extension will trigger a PHP :php:`E_USER_DEPRECATED` error.


Migration
=========

Rename file name from :file:`.txt` to :file:`.typoscript` extension.
For example code like:

.. code-block:: typoscript

   <INCLUDE_TYPOSCRIPT: source="FILE:EXT:form/Configuration/TypoScript/setup.txt">
   <INCLUDE_TYPOSCRIPT: source="FILE:EXT:fluid_styled_content/Configuration/TypoScript/constants.txt">

should be changed to:

.. code-block:: typoscript

   <INCLUDE_TYPOSCRIPT: source="FILE:EXT:form/Configuration/TypoScript/setup.typoscript">
   <INCLUDE_TYPOSCRIPT: source="FILE:EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript">


.. index:: TSConfig, TypoScript, NotScanned
