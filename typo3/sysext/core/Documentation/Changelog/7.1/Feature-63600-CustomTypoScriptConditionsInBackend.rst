
.. include:: ../../Includes.txt

====================================================================
Feature: #61489 - Allow own TypoScript Conditions in Backend as well
====================================================================

See :issue:`61489`

Description
===========

It is now possible to add own TypoScript conditions via a separate API in the Backend. The API for the frontend
was introduced with TYPO3 CMS 7.0.

An extension/package can now ship an implementation of a new abstract class AbstractCondition. Via the existing
TypoScript Condition Syntax the class is called by the simple fully namespaced class name.
The class' main function "matchCondition" can flexibly evaluate any parameters given after the class name.

Usage:

.. code-block:: typoscript

	[BigCompanyName\TypoScriptLovePackage\MyCustomTypoScriptCondition]

	[BigCompanyName\TypoScriptLovePackage\MyCustomTypoScriptCondition = 7]

	[BigCompanyName\TypoScriptLovePackage\MyCustomTypoScriptCondition = 7, != 6]

	[BigCompanyName\TypoScriptLovePackage\MyCustomTypoScriptCondition = {$mysite.myconstant}]

where the TypoScript Condition class deals with =/!= etc itself.

Impact
======

If you've previously used the `userFunc` condition, you are encouraged to use this new API for your own TypoScript
conditions.
