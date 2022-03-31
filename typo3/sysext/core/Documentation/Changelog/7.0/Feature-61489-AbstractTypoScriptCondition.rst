
.. include:: /Includes.rst.txt

================================================================
Feature: #61489 - Allow own TypoScript Condition implementations
================================================================

See :issue:`61489`

Description
===========

It is now possible to add own TypoScript conditions via a
separate API.

An extension / package can now ship an implementation of a new
abstract class AbstractCondition. Via the existing TypoScript
Condition Syntax the class is called by the simple full namespaced
class name.
The class's main function "matchCondition" can flexibly evaluate
any parameters given after the class name.

Usage:

.. code-block:: typoscript

	[BigCompanyName\TypoScriptLovePackage\BennisTypoScriptCondition]

	[BigCompanyName\TypoScriptLovePackage\BennisTypoScriptCondition = 7]

	[BigCompanyName\TypoScriptLovePackage\BennisTypoScriptCondition = 7, != 6]

	[BigCompanyName\TypoScriptLovePackage\BennisTypoScriptCondition = {$mysite.myconstant}]

where the TypoScript Condition class deals with =/!= etc itself.

Impact
======

If you've previously used the "userFunc" condition, you are encouraged
to use this new API for your own TypoScript conditions.


.. index:: PHP-API, TypoScript, Frontend
